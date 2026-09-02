<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Inference\EmbeddingModelClient;
use AiGateway\Domain\GatewayException;
use AiGateway\Infrastructure\Http\JsonHttpClient;

/**
 * Adapter Gemini embed — HTTP, pas de SDK Google.
 *
 * Pourquoi : AIGW-004. batchEmbedContents. La famille reste une donnée SQL.
 *
 * Ne fait pas : generate ; mélanger MiniLM et Gemini.
 *
 * @return list<list<float>>
 */
final class GeminiEmbeddingClient implements EmbeddingModelClient
{
    public function __construct(
        private readonly JsonHttpClient $http,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com',
    ) {
    }

    public function embed(string $model, array $texts, float $timeoutSeconds): array
    {
        if ($this->apiKey === '') {
            throw GatewayException::unavailable('Clé Gemini absente.');
        }
        $requests = [];
        foreach ($texts as $text) {
            $requests[] = [
                'model' => 'models/' . $model,
                'content' => ['parts' => [['text' => $text]]],
            ];
        }
        $url = rtrim($this->baseUrl, '/') . '/v1beta/models/' . rawurlencode($model) . ':batchEmbedContents';
        $result = $this->http->postJson($url, ['x-goog-api-key' => $this->apiKey], ['requests' => $requests], $timeoutSeconds);
        if ($result['status'] !== 200 || !is_array($result['body'])) {
            throw GatewayException::unavailable('Gemini embed HTTP ' . $result['status'] . '.');
        }
        $embeddings = $result['body']['embeddings'] ?? [];
        if (!is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw GatewayException::unavailable('Réponse embed Gemini invalide.');
        }
        $vectors = [];
        foreach ($embeddings as $item) {
            $values = $item['values'] ?? [];
            if (!is_array($values)) {
                throw GatewayException::unavailable('Vecteur Gemini invalide.');
            }
            $vectors[] = array_map(static fn (mixed $n): float => (float) $n, $values);
        }

        return $vectors;
    }
}
