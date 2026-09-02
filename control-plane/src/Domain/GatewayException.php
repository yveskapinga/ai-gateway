<?php

declare(strict_types=1);

namespace AiGateway\Domain;

/**
 * Erreur métier du gateway, destinée au listener HTTP unique.
 *
 * Pourquoi : AIGW-005 / AIGW-002 INV-006 — un code machine + message, jamais un
 * texte de modèle inventé quand l’inférence est down.
 *
 * Garantit : `errorCode` stable (préfixe AIGW.) et un status HTTP déjà choisi.
 *
 * Ne fait pas : journaliser des secrets ; appeler Ollama/Gemini.
 *
 * Debug : chercher `errorCode` dans les logs et `gateway_run.error_code`.
 */
final class GatewayException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
        public readonly mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function missingKey(): self
    {
        return new self('AIGW.AUTH.MISSING_KEY', 'Clé d’API requise.', 401);
    }

    public static function invalidKey(): self
    {
        return new self('AIGW.AUTH.INVALID_KEY', 'Clé d’API invalide.', 401);
    }

    public static function notFound(string $path): self
    {
        return new self('AIGW.COMMON.NOT_FOUND', 'Ressource introuvable.', 404, ['path' => $path]);
    }

    public static function validation(string $message, mixed $details = null): self
    {
        return new self('AIGW.VALIDATION.FAILED', $message, 422, $details);
    }

    public static function unavailable(string $message = 'Inférence indisponible.'): self
    {
        return new self('AIGW.INFERENCE.UNAVAILABLE', $message, 503);
    }

    public static function internal(): self
    {
        return new self('AIGW.COMMON.INTERNAL', 'Une erreur interne est survenue.', 500);
    }

    public static function unknownRoute(string $task, string $complexity): self
    {
        return new self('AIGW.ROUTE.UNKNOWN', 'Route inconnue pour cette tâche / complexité.', 422, [
            'task' => $task,
            'complexity' => $complexity,
        ]);
    }

    public static function unknownFamily(string $family): self
    {
        return new self('AIGW.EMBED.UNKNOWN_FAMILY', 'Famille d’embeddings inconnue.', 422, [
            'family' => $family,
        ]);
    }
}
