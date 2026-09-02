<?php

declare(strict_types=1);

namespace AiGateway\Domain;

/**
 * Résultat d’un embed — une famille, une dimension.
 *
 * Pourquoi : AIGW-002 INV-003. Mélanger 768 et 384 est une erreur métier.
 * Ne fait pas : generate.
 *
 * @param list<list<float>> $vectors
 */
final readonly class EmbedResult
{
    /**
     * @param list<list<float>> $vectors
     */
    public function __construct(
        public array $vectors,
        public string $family,
        public int $dimension,
        public string $provider,
        public string $model,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(string $correlationId): array
    {
        return [
            'vectors' => $this->vectors,
            'family' => $this->family,
            'dimension' => $this->dimension,
            'provider' => $this->provider,
            'model' => $this->model,
            'correlationId' => $correlationId,
        ];
    }
}
