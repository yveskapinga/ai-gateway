<?php

declare(strict_types=1);

namespace AiGateway\Application\Platform;

use AiGateway\Application\Persistence\RoutineExecutor;
use AiGateway\Domain\GatewayException;

/**
 * Append-only des exécutions (P1). Le prompt n’est pas un champ de cette API.
 *
 * Pourquoi : AIGW-002 INV-008 / INV-010. Audit ≠ vérité du modèle.
 *
 * Garantit : appelle uniquement `aigw_run_append` ; refuse un correlation_id vide.
 *
 * Ne fait pas : stocker le prompt ; décider primary/fallback (P3–P5).
 *
 * Debug : table gateway_run, tri recorded_at desc, 20 lignes.
 */
final class RecordRunService
{
    public function __construct(private readonly RoutineExecutor $routines)
    {
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function append(array $fields): array
    {
        $correlationId = trim((string) ($fields['correlation_id'] ?? ''));
        if ($correlationId === '') {
            throw GatewayException::validation('correlation_id requis.');
        }
        if (array_key_exists('prompt', $fields)) {
            throw GatewayException::validation('Le prompt ne s’enregistre pas dans gateway_run.');
        }

        $row = $this->routines->call('aigw_run_append', [
            'application_code' => $fields['application_code'] ?? null,
            'task' => $fields['task'] ?? null,
            'complexity' => $fields['complexity'] ?? null,
            'route_id' => $fields['route_id'] ?? null,
            'provider_code' => $fields['provider_code'] ?? null,
            'model_code' => $fields['model_code'] ?? null,
            'status' => $fields['status'] ?? 'ok',
            'latency_ms' => $fields['latency_ms'] ?? null,
            'error_code' => $fields['error_code'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        if (!is_array($row)) {
            throw GatewayException::internal();
        }

        /** @var array<string, mixed> $row */
        return $row;
    }
}
