<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http\Response;

/**
 * Identifiant de corrélation request / logs / audit.
 *
 * Pourquoi : AIGW-005 — relier un appel HTTP à gateway_run et aux logs.
 * Ne fait pas : authentifier (ce n’est pas une identité).
 *
 * Debug : header X-Correlation-Id ; colonne correlation_id de gateway_run.
 */
final class CorrelationId
{
    public static function resolve(?string $incoming): string
    {
        $incoming = $incoming !== null ? trim($incoming) : '';
        if ($incoming !== '' && preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $incoming) === 1) {
            return $incoming;
        }

        return bin2hex(random_bytes(16));
    }
}
