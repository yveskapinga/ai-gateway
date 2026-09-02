<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Controller;

use AiGateway\Application\Platform\HealthService;
use AiGateway\ControlPlane\Http\Response\JsonEnvelope;

/**
 * Endpoint santé — sonde d’exploitation uniquement.
 *
 * Pourquoi : AIGW-005. Un load balancer interroge sans clé d’API.
 *
 * Garantit : contrat unique. N’invente pas de JSON local.
 *
 * Ne fait pas : generate/embed, SQL inline.
 *
 * Debug : GET /v1/health ; checks.database.
 */
final class HealthController
{
    public function __construct(private readonly HealthService $health)
    {
    }

    public function __invoke(string $correlationId): JsonEnvelope
    {
        $status = $this->health->check();
        $http = $status->status === 'unavailable' ? 503 : 200;

        return JsonEnvelope::ok($status->toArray(), $correlationId, $http);
    }
}
