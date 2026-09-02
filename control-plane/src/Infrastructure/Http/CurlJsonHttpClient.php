<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Http;

/**
 * Client HTTP JSON de production (curl). Loopback Ollama / Gemini.
 *
 * Ne fait pas : retry métier (c’est GenerateService) ; stocker la clé.
 */
final class CurlJsonHttpClient implements JsonHttpClient
{
    public function postJson(string $url, array $headers, array $payload, float $timeoutSeconds): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => null, 'error' => 'curl_init failed'];
        }
        $headerLines = ['Content-Type: application/json'];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) max(1, $timeoutSeconds),
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = $errno !== 0 ? curl_error($ch) : null;
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            return ['status' => 0, 'body' => null, 'error' => $error ?: 'timeout'];
        }
        $decoded = json_decode((string) $raw, true);

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'error' => $status >= 400 ? substr((string) $raw, 0, 200) : null,
        ];
    }
}
