<?php

declare(strict_types=1);

use AiGateway\Application\Inference\MapGenerativeClientLocator;
use AiGateway\Application\Routing\RouteService;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\Infrastructure\Http\CurlJsonHttpClient;
use AiGateway\Infrastructure\Inference\GeminiGenerativeClient;
use AiGateway\Infrastructure\Inference\OllamaGenerativeClient;

require dirname(__DIR__, 2) . '/control-plane/vendor/autoload.php';

$envFiles = [
    dirname(__DIR__, 2) . '/.env',
    (string) getenv('AIGW_ENV_FILE'),
];
foreach ($envFiles as $envFile) {
    if ($envFile === '' || !is_file($envFile) || !is_readable($envFile)) {
        continue;
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\"'");
        if ($name !== '' && getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

$http = new CurlJsonHttpClient();
$geminiKey = (string) (getenv('GEMINI_API_KEY') ?: '');
[$front, $routines] = ControlPlaneFactory::inMemoryGenerateFrontController(
    new MapGenerativeClientLocator([
        'ollama' => new OllamaGenerativeClient($http),
        'gemini' => new GeminiGenerativeClient($http, $geminiKey),
    ]),
    true,
    getenv('AIGW_API_KEY') ?: 'dev_only_change_me',
);

// Gemini d’abord (qualité RAG / JSON), Llama 3.2 3B seulement en secours.
(new RouteService($routines))->upsert(
    'generate',
    'low',
    'gemini',
    'gemini-flash-lite-latest',
    'generate',
    'ollama',
    'llama3.2:3b',
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with((string) $key, 'HTTP_') && is_string($value)) {
        $name = str_replace('_', '-', strtolower(substr((string) $key, 5)));
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

$envelope = $front->handle($method, $path, $headers, $body);
http_response_code($envelope->httpStatus);
header('Content-Type: application/json; charset=utf-8');
header('X-Correlation-Id: ' . $envelope->correlationId);
echo json_encode($envelope->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
