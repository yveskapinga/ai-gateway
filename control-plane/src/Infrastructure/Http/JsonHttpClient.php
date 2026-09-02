<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Http;

/**
 * Transport JSON HTTP — utilisé par les adapters, pas par le Domain.
 *
 * Pourquoi : tests P4/P5 injectent un fake (pas de clé Gemini en CI).
 * Ne fait pas : choisir le modèle.
 *
 * @return array{status: int, body: array<string, mixed>|null, error: string|null}
 */
interface JsonHttpClient
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     * @return array{status: int, body: array<string, mixed>|null, error: string|null}
     */
    public function postJson(string $url, array $headers, array $payload, float $timeoutSeconds): array;
}
