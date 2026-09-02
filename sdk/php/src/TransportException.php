<?php

declare(strict_types=1);

namespace AiGateway\Sdk;

/**
 * Échec de transport vers le gateway — distinct d’une erreur métier AIGW.*.
 *
 * Pourquoi : AIGW-006. Timeout / hôte down ≠ complétion.
 * Garantit : jamais de champ `text` inventé sur ce chemin.
 * Ne fait pas : choisir un provider local vs cloud ; lire une clé de modèle.
 *
 * Debug : errorCode AIGW.PEP.TIMEOUT | AIGW.PEP.UNAVAILABLE.
 */
final class TransportException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 503,
        public readonly ?string $correlationId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function timeout(?string $correlationId = null): self
    {
        return new self('AIGW.PEP.TIMEOUT', 'Le gateway n’a pas répondu à temps.', 504, $correlationId);
    }

    public static function unavailable(string $message = 'Gateway injoignable.', ?string $correlationId = null): self
    {
        return new self('AIGW.PEP.UNAVAILABLE', $message, 503, $correlationId);
    }
}
