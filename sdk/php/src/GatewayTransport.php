<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Transport vers le control plane — pas un second moteur.
 *
 * Pourquoi : AIGW-006. Le SDK ne connaît que des chemins /v1.
 *
 * @return array{status: int, body: array<string, mixed>|null, correlationId: string|null, error: string|null}
 */
interface GatewayTransport
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $body
     * @return array{status: int, body: array<string, mixed>|null, correlationId: string|null, error: string|null}
     */
    public function request(string $method, string $path, array $headers = [], array $body = []): array;
}
