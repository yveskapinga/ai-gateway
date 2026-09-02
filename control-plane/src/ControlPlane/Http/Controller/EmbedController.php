<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Controller;

use AiGateway\Application\Inference\EmbedService;
use AiGateway\ControlPlane\Http\Response\JsonEnvelope;

/**
 * POST /v1/embed — AIGW-005. family obligatoire.
 *
 * Ne fait pas : generate.
 */
final class EmbedController
{
    public function __construct(private readonly EmbedService $embed)
    {
    }

    /**
     * @param array<string, mixed> $body
     */
    public function __invoke(array $body, string $correlationId): JsonEnvelope
    {
        $result = $this->embed->embed($body, $correlationId);

        return JsonEnvelope::ok($result->toArray($correlationId), $correlationId);
    }
}
