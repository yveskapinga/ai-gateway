<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

use AiGateway\Domain\GatewayException;

/**
 * Table kind → client. P4 : ollama seulement. P5 ajoute gemini.
 *
 * Ne fait pas : connaître les apps consommatrices.
 */
final class MapGenerativeClientLocator implements GenerativeClientLocator
{
    /**
     * @param array<string, GenerativeModelClient> $byKind
     */
    public function __construct(private readonly array $byKind)
    {
    }

    public function forKind(string $kind): GenerativeModelClient
    {
        if (!isset($this->byKind[$kind])) {
            throw GatewayException::unavailable('Aucun adapter pour le provider ' . $kind . '.');
        }

        return $this->byKind[$kind];
    }
}
