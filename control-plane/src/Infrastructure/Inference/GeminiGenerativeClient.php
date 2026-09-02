<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Inference\GenerativeModelClient;
use AiGateway\Domain\GatewayException;
use AiGateway\Domain\GenerateResult;
use AiGateway\Infrastructure\Http\JsonHttpClient;

/**
 * Adapter Gemini generate — HTTP, pas de SDK Google dans le Domain.
 *
 * Pourquoi : AIGW-004 / INV-011. La clé vient d’un nom d’env, jamais de SQL.
 *
 * Garantit : 429 / 5xx / timeout / JSON invalide → GatewayException (failover P5).
 *
 * Ne fait pas : RAG ; stocker la clé ; décider la route.
 *
 * Debug : POST /v1beta/models/{model}:generateContent ; candidates[0].content.parts[0].text.
 */
final class GeminiGenerativeClient implements GenerativeModelClient
{
    public function __construct(
        private readonly JsonHttpClient $http,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com',
        private readonly string $providerCode = 'gemini',
    ) {
    }

    public function generate(string $model, string $prompt, ?array $jsonSchema, float $timeoutSeconds): GenerateResult
    {
        if ($this->apiKey === '') {
            throw GatewayException::unavailable('Clé Gemini absente (nom d’env, pas une valeur SQL).');
        }

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ];
        if ($jsonSchema !== null) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $jsonSchema,
            ];
        }

        $url = rtrim($this->baseUrl, '/') . '/v1beta/models/' . rawurlencode($model) . ':generateContent';
        $result = $this->http->postJson(
            $url,
            ['x-goog-api-key' => $this->apiKey],
            $payload,
            $timeoutSeconds,
        );

        if (($result['error'] ?? null) === 'timeout' || $result['status'] === 0) {
            throw GatewayException::unavailable('Gemini timeout ou injoignable.');
        }
        if ($result['status'] === 429 || $result['status'] >= 500) {
            throw GatewayException::unavailable('Gemini HTTP ' . $result['status'] . '.');
        }
        $body = $result['body'];
        $text = '';
        if (is_array($body)) {
            $text = (string) ($body['candidates'][0]['content']['parts'][0]['text'] ?? '');
        }
        if ($text === '') {
            throw GatewayException::unavailable('Réponse Gemini vide ou invalide.');
        }
        $json = null;
        if ($jsonSchema !== null) {
            $decoded = json_decode($text, true);
            $json = is_array($decoded) ? $decoded : null;
        }

        return new GenerateResult($text, $this->providerCode, $model, $json);
    }
}
