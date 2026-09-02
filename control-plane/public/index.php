<?php

declare(strict_types=1);

/**
 * Entrée HTTP mince (AIGW-004). FrontController, pas un second moteur.
 * P1 : GET /v1/health. Generate/embed arrivent P4/P6.
 */

use AiGateway\ControlPlane\Http\ControlPlaneFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_') && is_string($value)) {
        $name = str_replace('_', '-', strtolower(substr($key, 5)));
        $headers[$name] = $value;
    }
}
if (isset($_SERVER['HTTP_X_API_KEY']) && is_string($_SERVER['HTTP_X_API_KEY'])) {
    $headers['x-api-key'] = $_SERVER['HTTP_X_API_KEY'];
}

$raw = file_get_contents('php://input') ?: '';
$body = [];
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    $body = is_array($decoded) ? $decoded : [];
}

$apiKey = getenv('AIGW_API_KEY') ?: null;
$front = ControlPlaneFactory::inMemoryFrontController($apiKey);
$envelope = $front->handle($method, $path, $headers, $body);

http_response_code($envelope->httpStatus);
header('Content-Type: application/json; charset=utf-8');
header('X-Correlation-Id: ' . $envelope->correlationId);
echo json_encode($envelope->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
