<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Response;

/**
 * Enveloppe HTTP unique du control plane.
 *
 * Pourquoi : AIGW-005 — succès = payload + correlationId ; erreur = { error: { code, message, details, correlationId } }.
 *
 * Garantit : correlationId toujours présent. Pas de stack trace dans le body.
 *
 * Ne fait pas : inférer ; ce n’est pas un adapter modèle.
 *
 * Debug : header X-Correlation-Id ; body.error.code.
 *
 * @param array<string, mixed> $body
 */
final readonly class JsonEnvelope
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        public int $httpStatus,
        public array $body,
        public string $correlationId,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function ok(array $payload, string $correlationId, int $httpStatus = 200): self
    {
        $payload['correlationId'] = $correlationId;

        return new self($httpStatus, $payload, $correlationId);
    }

    public static function error(string $code, string $message, string $correlationId, int $httpStatus, mixed $details = null): self
    {
        return new self($httpStatus, [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
                'correlationId' => $correlationId,
            ],
        ], $correlationId);
    }
}
