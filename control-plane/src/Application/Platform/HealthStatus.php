<?php

declare(strict_types=1);

namespace AiGateway\Application\Platform;

/**
 * Résultat d’une sonde — pas une inférence.
 *
 * Pourquoi : AIGW-005 health. status ok|degraded|unavailable.
 * Ne fait pas : appeler un LLM.
 *
 * @param array<string, mixed> $checks
 */
final readonly class HealthStatus
{
    /**
     * @param array<string, mixed> $checks
     */
    public function __construct(
        public string $status,
        public string $code,
        public array $checks,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'code' => $this->code,
            'checks' => $this->checks,
        ];
    }
}
