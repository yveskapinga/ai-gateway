<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

use AiGateway\Domain\GatewayException;

/**
 * kind → client embed. P6 : gemini. Pas de mélange de familles ici.
 */
final class MapEmbeddingClientLocator implements EmbeddingClientLocator
{
    /**
     * @param array<string, EmbeddingModelClient> $byKind
     */
    public function __construct(private readonly array $byKind)
    {
    }

    public function forKind(string $kind): EmbeddingModelClient
    {
        if (!isset($this->byKind[$kind])) {
            throw GatewayException::unavailable('Aucun adapter embed pour ' . $kind . '.');
        }

        return $this->byKind[$kind];
    }
}
