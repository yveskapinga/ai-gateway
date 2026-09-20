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
        $result = $this->requestWithRetry($url, $payload, $timeoutSeconds);

        if (($result['error'] ?? null) === 'timeout' || $result['status'] === 0) {
            throw GatewayException::unavailable('Gemini timeout ou injoignable.');
        }
        if ($result['status'] === 429 || $result['status'] >= 500) {
            throw GatewayException::unavailable('Gemini HTTP ' . $result['status'] . '.');
        }
        $body = $result['body'];
        if ($result['status'] >= 400) {
            $detail = is_array($body) ? (string) ($body['error']['message'] ?? '') : '';
            error_log('aigw gemini http=' . $result['status'] . ($detail !== '' ? ' ' . substr($detail, 0, 180) : ''));
            throw GatewayException::unavailable('Gemini HTTP ' . $result['status'] . '.');
        }
        $text = '';
        if (is_array($body)) {
            $text = (string) ($body['candidates'][0]['content']['parts'][0]['text'] ?? '');
        }
        if ($text === '') {
            $reason = is_array($body) ? (string) ($body['candidates'][0]['finishReason'] ?? '') : '';
            error_log('aigw gemini empty_text finishReason=' . $reason);
            throw GatewayException::unavailable('Réponse Gemini vide ou invalide.');
        }

        return new GenerateResult($text, $this->providerCode, $model, StructuredGenerateParser::decode($text, $jsonSchema));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status: int, body: array<string, mixed>|null, error: string|null}
     */
    private function requestWithRetry(string $url, array $payload, float $timeoutSeconds): array
    {
        $delay = 1;
        $last = ['status' => 0, 'body' => null, 'error' => 'timeout'];
        for ($attempt = 1; $attempt <= 3; ++$attempt) {
            $last = $this->http->postJson($url, ['x-goog-api-key' => $this->apiKey], $payload, $timeoutSeconds);
            $status = (int) ($last['status'] ?? 0);
            if (!in_array($status, [429, 500, 503], true) || $attempt === 3) {
                return $last;
            }
            if (defined('PHPUNIT_COMPOSER_INSTALL')) {
                return $last;
            }
            sleep($delay);
            $delay = min(8, $delay * 2);
        }

        return $last;
    }
}
