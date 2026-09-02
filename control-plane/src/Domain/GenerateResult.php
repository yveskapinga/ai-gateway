<?php

declare(strict_types=1);

namespace AiGateway\Domain;

/**
 * Résultat d’une génération réussie — pas un fallback inventé.
 *
 * Pourquoi : AIGW-005. provider/model sont ceux réellement utilisés.
 * Ne fait pas : choisir la route (SQL) ; masquer un échec par du lorem.
 */
final readonly class GenerateResult
{
    public function __construct(
        public string $text,
        public string $provider,
        public string $model,
        public ?array $json = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(string $correlationId): array
    {
        $payload = [
            'text' => $this->text,
            'provider' => $this->provider,
            'model' => $this->model,
            'correlationId' => $correlationId,
        ];
        if ($this->json !== null) {
            $payload['json'] = $this->json;
        }

        return $payload;
    }
}
