<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Inference\GenerativeModelClient;
use AiGateway\Domain\GatewayException;
use AiGateway\Domain\GenerateResult;
use AiGateway\Infrastructure\Http\JsonHttpClient;

/**
 * Adapter Ollama generate — HTTP local uniquement.
 *
 * Pourquoi : AIGW-004. Endpoint 127.0.0.1:11434, jamais un bind public ici.
 *
 * Garantit : timeout / 5xx → GatewayException, pas de texte inventé.
 *
 * Ne fait pas : décider la route ; lire GEMINI_API_KEY.
 *
 * Debug : POST {endpoint}/api/generate ; champ response.
 */
final class OllamaGenerativeClient implements GenerativeModelClient
{
    public function __construct(
        private readonly JsonHttpClient $http,
        private readonly string $baseUrl = 'http://127.0.0.1:11434',
        private readonly string $providerCode = 'ollama',
    ) {
    }

    public function generate(string $model, string $prompt, ?array $jsonSchema, float $timeoutSeconds): GenerateResult
    {
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ];
        if ($jsonSchema !== null) {
            $payload['format'] = $jsonSchema;
        }

        $result = $this->http->postJson(
            rtrim($this->baseUrl, '/') . '/api/generate',
            [],
            $payload,
            $timeoutSeconds,
        );

        if (($result['error'] ?? null) === 'timeout' || $result['status'] === 0) {
            throw GatewayException::unavailable('Ollama timeout ou injoignable.');
        }
        if ($result['status'] === 429 || $result['status'] >= 500) {
            throw GatewayException::unavailable('Ollama HTTP ' . $result['status'] . '.');
        }
        $body = $result['body'];
        $text = is_array($body) ? (string) ($body['response'] ?? '') : '';
        if ($text === '') {
            throw GatewayException::unavailable('Réponse Ollama vide ou invalide.');
        }
        $json = null;
        if ($jsonSchema !== null) {
            $decoded = json_decode($text, true);
            $json = is_array($decoded) ? $decoded : null;
        }

        return new GenerateResult($text, $this->providerCode, $model, $json);
    }
}
