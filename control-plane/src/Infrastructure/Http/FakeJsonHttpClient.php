<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Http;

/**
 * Fake HTTP pour CI (P4–P5). Pas de réseau, pas de clé.
 *
 * @param \Closure(string, array<string, string>, array<string, mixed>, float): array{status:int, body:?array, error:?string} $handler
 */
final class FakeJsonHttpClient implements JsonHttpClient
{
    /**
     * @param \Closure(string, array<string, string>, array<string, mixed>, float): array{status: int, body: array<string, mixed>|null, error: string|null} $handler
     */
    public function __construct(private readonly \Closure $handler)
    {
    }

    public function postJson(string $url, array $headers, array $payload, float $timeoutSeconds): array
    {
        return ($this->handler)($url, $headers, $payload, $timeoutSeconds);
    }
}
