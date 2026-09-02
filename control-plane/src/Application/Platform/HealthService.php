<?php

declare(strict_types=1);

namespace AiGateway\Application\Platform;

use AiGateway\Application\Persistence\RoutineExecutor;

/**
 * Vérifie que le control plane peut parler à la persistance.
 *
 * Pourquoi : AIGW-005 / AIGW-004. Un load balancer interroge sans passer par generate.
 *
 * Garantit : base down → degraded/unavailable, jamais un texte de modèle.
 *
 * Ne fait pas : inférer, authentifier une app métier, exposer Ollama.
 *
 * Debug : GET /v1/health ; checks.database.
 */
final class HealthService
{
    public function __construct(
        private readonly ?RoutineExecutor $routines,
        private readonly bool $databaseConfigured,
        private readonly ?InferenceLivenessProbe $ollama = null,
    ) {
    }

    public function check(): HealthStatus
    {
        if (!$this->databaseConfigured || $this->routines === null) {
            return new HealthStatus(
                'degraded',
                'AIGW.PLATFORM.HEALTH_DEGRADED',
                ['database' => 'not_configured'],
            );
        }

        try {
            $this->routines->call('aigw_health_ping');
        } catch (\Throwable) {
            return new HealthStatus(
                'unavailable',
                'AIGW.PLATFORM.HEALTH_UNAVAILABLE',
                ['database' => 'unreachable'],
            );
        }

        $checks = ['database' => 'ok'];
        if ($this->ollama !== null) {
            $checks['ollama'] = $this->ollama->ping() ? 'ok' : 'down';
        }

        if (($checks['ollama'] ?? 'ok') === 'down') {
            return new HealthStatus(
                'degraded',
                'AIGW.PLATFORM.HEALTH_DEGRADED',
                $checks,
            );
        }

        return new HealthStatus(
            'ok',
            'AIGW.PLATFORM.HEALTH_OK',
            $checks,
        );
    }
}
