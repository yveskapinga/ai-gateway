<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Client HTTP vers /v1 — pas un second moteur, pas un SDK Google.
 *
 * Pourquoi : AIGW-006. Timeout explicite ; protocole unique AIGW-005.
 * Garantit : échec réseau → TransportException, jamais une complétion.
 * Ne fait pas : embarquer une clé cloud de provider ; binder un moteur local.
 *
 * Debug : baseUrl + X-API-Key (clé gateway) + X-Correlation-Id.
 */
final class HttpTransport implements GatewayTransport
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutMs = 150000,
        private readonly array $headers = [],
    ) {
    }

    public function request(string $method, string $path, array $headers = [], array $body = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;
        $timeoutSec = max(1, (int) ceil($this->timeoutMs / 1000));
        $merged = array_merge($this->headers, $headers);
        $headerLines = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        foreach ($merged as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $http = [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
        ];
        if ($method !== 'GET') {
            $http['content'] = json_encode($body, JSON_THROW_ON_ERROR);
        }
        $ctx = stream_context_create(['http' => $http]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw TransportException::timeout();
        }
        $decoded = json_decode($raw, true);
        $correlation = null;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (stripos($line, 'X-Correlation-Id:') === 0) {
                    $correlation = trim(substr($line, strlen('X-Correlation-Id:')));
                }
            }
        }
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) === 1) {
            $status = (int) $m[1];
        }

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'correlationId' => $correlation,
            'error' => is_array($decoded) ? null : 'non-json',
        ];
    }
}
