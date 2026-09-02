<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Complétion renvoyée par le gateway — provider/model réels, pas une route locale.
 *
 * Pourquoi : AIGW-005 / AIGW-006.
 * Ne fait pas : choisir le modèle.
 */
final readonly class GenerateSuccess
{
    /**
     * @param array<string, mixed>|null $json
     */
    public function __construct(
        public string $text,
        public string $provider,
        public string $model,
        public string $correlationId,
        public ?array $json = null,
    ) {
    }
}
