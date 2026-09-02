<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Persistence;

use AiGateway\Application\Persistence\RoutineExecutor;
use AiGateway\Domain\GatewayException;

/**
 * Appelle les fonctions PostgreSQL whitelistées (SQL-first).
 *
 * Pourquoi : ADR-001 — PDO transporte des routines, pas du SQL libre.
 *
 * Debug : SELECT aigw_health_ping(); SELECT * FROM gateway_run;
 */
final class PostgresRoutineExecutor implements RoutineExecutor
{
    public function __construct(private readonly \PDO $pdo)
    {
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function call(string $routine, array $params = []): mixed
    {
        return match ($routine) {
            'aigw_health_ping' => $this->healthPing(),
            'aigw_run_append' => $this->runAppend($params),
            'aigw_application_create' => $this->jsonCall(
                'SELECT aigw_application_create(:code, :status)',
                ['code' => $params['code'] ?? '', 'status' => $params['status'] ?? 'active'],
            ),
            'aigw_application_get' => $this->jsonCall(
                'SELECT aigw_application_get(:code)',
                ['code' => $params['code'] ?? ''],
            ),
            'aigw_provider_create' => $this->jsonCall(
                'SELECT aigw_provider_create(:code, :kind, :endpoint, :secret_env_name)',
                [
                    'code' => $params['code'] ?? '',
                    'kind' => $params['kind'] ?? '',
                    'endpoint' => $params['endpoint'] ?? '',
                    'secret_env_name' => $params['secret_env_name'] ?? null,
                ],
            ),
            'aigw_provider_list' => $this->jsonCall('SELECT aigw_provider_list()', []),
            'aigw_model_create' => $this->jsonCall(
                'SELECT aigw_model_create(:provider_code, :code, :capability, :family, :dimension, :relative_cost)',
                [
                    'provider_code' => $params['provider_code'] ?? '',
                    'code' => $params['code'] ?? '',
                    'capability' => $params['capability'] ?? '',
                    'family' => $params['family'] ?? null,
                    'dimension' => $params['dimension'] ?? null,
                    'relative_cost' => $params['relative_cost'] ?? 1,
                ],
            ),
            'aigw_model_get' => $this->jsonCall(
                'SELECT aigw_model_get(:provider_code, :code, :capability)',
                [
                    'provider_code' => $params['provider_code'] ?? '',
                    'code' => $params['code'] ?? '',
                    'capability' => $params['capability'] ?? '',
                ],
            ),
            'aigw_route_upsert' => $this->jsonCall(
                'SELECT aigw_route_upsert(:task, :complexity, :primary_provider_code, :primary_model_code, :primary_capability, :fallback_provider_code, :fallback_model_code, :fallback_capability)',
                [
                    'task' => $params['task'] ?? '',
                    'complexity' => $params['complexity'] ?? '',
                    'primary_provider_code' => $params['primary_provider_code'] ?? '',
                    'primary_model_code' => $params['primary_model_code'] ?? '',
                    'primary_capability' => $params['primary_capability'] ?? 'generate',
                    'fallback_provider_code' => $params['fallback_provider_code'] ?? null,
                    'fallback_model_code' => $params['fallback_model_code'] ?? null,
                    'fallback_capability' => $params['fallback_capability'] ?? 'generate',
                ],
            ),
            'aigw_route_resolve' => $this->jsonCall(
                'SELECT aigw_route_resolve(:task, :complexity)',
                ['task' => $params['task'] ?? '', 'complexity' => $params['complexity'] ?? ''],
            ),
            default => throw GatewayException::internal(),
        };
    }

    private function healthPing(): string
    {
        $value = $this->pdo->query('SELECT aigw_health_ping()')->fetchColumn();

        return is_string($value) ? $value : 'ok';
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function runAppend(array $params): array
    {
        $stmt = $this->pdo->prepare('SELECT aigw_run_append(:application_code, :task, :complexity, :route_id, :provider_code, :model_code, :status, :latency_ms, :error_code, :correlation_id)');
        $stmt->execute([
            'application_code' => $params['application_code'] ?? null,
            'task' => $params['task'] ?? null,
            'complexity' => $params['complexity'] ?? null,
            'route_id' => $params['route_id'] ?? null,
            'provider_code' => $params['provider_code'] ?? null,
            'model_code' => $params['model_code'] ?? null,
            'status' => $params['status'] ?? 'ok',
            'latency_ms' => $params['latency_ms'] ?? null,
            'error_code' => $params['error_code'] ?? null,
            'correlation_id' => $params['correlation_id'] ?? '',
        ]);
        $raw = $stmt->fetchColumn();
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            throw GatewayException::internal();
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|list<mixed>
     */
    private function jsonCall(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchColumn();
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            throw GatewayException::internal();
        }

        return $decoded;
    }
}
