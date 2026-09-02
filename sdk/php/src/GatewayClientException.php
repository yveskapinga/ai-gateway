<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Erreur métier renvoyée par le gateway (AIGW-005).
 *
 * Pourquoi : 422 / 401 / 503 JSON ≠ timeout. Ce n’est toujours pas une complétion.
 * Ne fait pas : inventer `text` ; résoudre une route.
 *
 * Debug : errorCode + correlationId.
 */
final class GatewayClientException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
        public readonly ?string $correlationId,
        public readonly mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
