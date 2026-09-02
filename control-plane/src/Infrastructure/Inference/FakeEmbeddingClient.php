<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Inference\EmbeddingModelClient;
use AiGateway\Domain\GatewayException;

/**
 * Fake embed pour CI — dimension imposée, pas de réseau.
 *
 * Ne fait pas : appeler Gemini.
 */
final class FakeEmbeddingClient implements EmbeddingModelClient
{
    public function __construct(private readonly int $dimension)
    {
    }

    public function embed(string $model, array $texts, float $timeoutSeconds): array
    {
        if ($this->dimension < 1) {
            throw GatewayException::internal();
        }
        $vectors = [];
        foreach ($texts as $i => $text) {
            $vectors[] = array_fill(0, $this->dimension, 0.01 * ($i + 1));
        }

        return $vectors;
    }
}
