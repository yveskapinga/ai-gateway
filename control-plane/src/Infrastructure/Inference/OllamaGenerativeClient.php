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
 * Garantit : timeout / 5xx / JSON incomplet → GatewayException, pas de texte inventé.
 * num_ctx 8192 : le défaut Ollama (2048) coupe un prompt RAG à 8 passages.
 *
 * Ne fait pas : décider la route ; lire GEMINI_API_KEY.
 *
 * Debug : POST {endpoint}/api/generate ; champ response.
 */
final class OllamaGenerativeClient implements GenerativeModelClient
{
    public const CONTEXT_TOKENS = 8192;

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
            'options' => [
                'num_ctx' => self::CONTEXT_TOKENS,
                'temperature' => 0.1,
            ],
        ];
        if ($jsonSchema !== null) {
            $payload['format'] = 'json';
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

        return new GenerateResult($text, $this->providerCode, $model, StructuredGenerateParser::decode($text, $jsonSchema));
    }
}
