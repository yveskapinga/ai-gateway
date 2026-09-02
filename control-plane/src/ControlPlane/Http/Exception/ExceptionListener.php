<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Exception;

use AiGateway\ControlPlane\Http\Response\JsonEnvelope;
use AiGateway\Domain\GatewayException;

/**
 * Unique usine HTTP des erreurs.
 *
 * Pourquoi : AIGW-005 — un seul schéma. Interdit les try/catch locaux qui renvoient un JSON différent.
 *
 * Garantit : GatewayException → code métier ; le reste → AIGW.COMMON.INTERNAL sans secret.
 *
 * Ne fait pas : retry, circuit-breaker, appel modèle.
 *
 * Debug : correlationId du header ; logs kernel.
 */
final class ExceptionListener
{
    public function toEnvelope(\Throwable $throwable, string $correlationId): JsonEnvelope
    {
        if ($throwable instanceof GatewayException) {
            return JsonEnvelope::error(
                $throwable->errorCode,
                $throwable->getMessage(),
                $correlationId,
                $throwable->httpStatus,
                $throwable->details,
            );
        }

        return JsonEnvelope::error(
            'AIGW.COMMON.INTERNAL',
            'Une erreur interne est survenue.',
            $correlationId,
            500,
        );
    }
}
