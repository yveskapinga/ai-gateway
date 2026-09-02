<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Transport injectable (tests, in-process) — toujours le control plane derrière.
 *
 * Pourquoi : AIGW-006. Contrats sans HTTP réel, sans second moteur.
 * Garantit : le closure ne doit pas appeler Ollama/Gemini.
 * Ne fait pas : RouteService ; GenerateService.
 *
 * Debug : FrontController::handle GET|POST /v1/*.
 */
final class CallableTransport implements GatewayTransport
{
    /**
     * @param \Closure(string, string, array<string, string>, array<string, mixed>): array{status: int, body: array<string, mixed>|null, correlationId: string|null, error: string|null} $handler
     */
    public function __construct(private readonly \Closure $handler)
    {
    }

    public function request(string $method, string $path, array $headers = [], array $body = []): array
    {
        return ($this->handler)($method, $path, $headers, $body);
    }
}
