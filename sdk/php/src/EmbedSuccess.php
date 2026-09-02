<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Embed renvoyé par le gateway — family + dimension du contrat SQL.
 *
 * Pourquoi : AIGW-002 INV-003. Le SDK ne mélange pas les familles.
 *
 * @param list<list<float>> $vectors
 */
final readonly class EmbedSuccess
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
        public string $correlationId,
    ) {
    }
}
