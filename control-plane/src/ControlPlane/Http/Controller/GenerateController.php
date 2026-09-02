<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Controller;

use AiGateway\Application\Inference\GenerateService;
use AiGateway\ControlPlane\Http\Response\JsonEnvelope;

/**
 * POST /v1/generate — AIGW-005. Pas de SQL ici.
 *
 * Ne fait pas : choisir Ollama vs Gemini (c’est la route SQL).
 */
final class GenerateController
{
    public function __construct(private readonly GenerateService $generate)
    {
    }

    /**
     * @param array<string, mixed> $body
     */
    public function __invoke(array $body, string $correlationId): JsonEnvelope
    {
        $result = $this->generate->generate($body, $correlationId);

        return JsonEnvelope::ok($result->toArray($correlationId), $correlationId);
    }
}
