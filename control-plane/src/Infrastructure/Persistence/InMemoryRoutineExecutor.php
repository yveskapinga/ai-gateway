<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Persistence;

use AiGateway\Application\Persistence\RoutineExecutor;
use AiGateway\Domain\GatewayException;

/**
 * Exécuteur de routines pour tests sans PostgreSQL.
 *
 * Pourquoi : les tests Pn ne doivent pas inventer un PASS SQL live.
 *
 * Garantit : les mêmes noms de routines que sql/routines/.
 *
 * Ne fait pas : remplacer Postgres en production.
 *
 * Debug : inspecter $this->runs / $this->applications / $this->providers.
 */
final class InMemoryRoutineExecutor implements RoutineExecutor
{
    /** @var list<array<string, mixed>> */
    public array $runs = [];

    /** @var array<string, array<string, mixed>> */
    public array $applications = [];

    /** @var array<string, array<string, mixed>> */
    public array $providers = [];

    /** @var array<string, array<string, mixed>> keyed by provider|code|capability */
    public array $models = [];

    /** @var array<string, array<string, mixed>> keyed by task|complexity */
    public array $routes = [];

    public function call(string $routine, array $params = []): mixed
    {
        return match ($routine) {
            'aigw_health_ping' => 'ok',
            'aigw_run_append' => $this->runAppend($params),
            'aigw_application_create' => $this->applicationCreate($params),
            'aigw_application_get' => $this->applicationGet($params),
            'aigw_provider_create' => $this->providerCreate($params),
            'aigw_provider_list' => array_values($this->providers),
            'aigw_model_create' => $this->modelCreate($params),
            'aigw_model_get' => $this->modelGet($params),
            'aigw_route_upsert' => $this->routeUpsert($params),
            'aigw_route_resolve' => $this->routeResolve($params),
            default => throw GatewayException::internal(),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function runAppend(array $params): array
    {
        $row = [
            'id' => bin2hex(random_bytes(16)),
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
            'recorded_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
        ];
        $this->runs[] = $row;

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function applicationCreate(array $params): array
    {
        $code = trim((string) ($params['code'] ?? ''));
        if ($code === '') {
            throw GatewayException::validation('code application requis.');
        }
        $row = [
            'id' => bin2hex(random_bytes(8)),
            'code' => $code,
            'status' => $params['status'] ?? 'active',
        ];
        $this->applications[$code] = $row;

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function applicationGet(array $params): array
    {
        $code = (string) ($params['code'] ?? '');
        if (!isset($this->applications[$code])) {
            throw GatewayException::notFound('application');
        }

        return $this->applications[$code];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function providerCreate(array $params): array
    {
        $code = trim((string) ($params['code'] ?? ''));
        $secret = $params['secret_env_name'] ?? null;
        if (is_string($secret) && $secret !== '' && preg_match('/^[A-Z][A-Z0-9_]{2,63}$/', $secret) !== 1) {
            throw GatewayException::validation('secret_env_name doit être un nom d’environnement, pas une clé.');
        }
        $row = [
            'id' => bin2hex(random_bytes(8)),
            'code' => $code,
            'kind' => $params['kind'] ?? '',
            'endpoint' => $params['endpoint'] ?? '',
            'secret_env_name' => $secret ?: null,
        ];
        $this->providers[$code] = $row;

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function modelCreate(array $params): array
    {
        $providerCode = (string) ($params['provider_code'] ?? '');
        if (!isset($this->providers[$providerCode])) {
            throw GatewayException::notFound('provider');
        }
        $key = $providerCode . '|' . $params['code'] . '|' . $params['capability'];
        $row = [
            'id' => bin2hex(random_bytes(8)),
            'provider_code' => $providerCode,
            'code' => $params['code'] ?? '',
            'capability' => $params['capability'] ?? '',
            'family' => $params['family'] ?? null,
            'dimension' => $params['dimension'] ?? null,
            'relative_cost' => $params['relative_cost'] ?? 1,
        ];
        $this->models[$key] = $row;

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function modelGet(array $params): array
    {
        $key = ($params['provider_code'] ?? '') . '|' . ($params['code'] ?? '') . '|' . ($params['capability'] ?? '');
        if (!isset($this->models[$key])) {
            throw GatewayException::notFound('model');
        }

        return $this->models[$key];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function routeUpsert(array $params): array
    {
        $key = ($params['task'] ?? '') . '|' . ($params['complexity'] ?? '');
        $row = [
            'id' => $this->routes[$key]['id'] ?? bin2hex(random_bytes(8)),
            'task' => $params['task'] ?? '',
            'complexity' => $params['complexity'] ?? '',
            'primary_provider_code' => $params['primary_provider_code'] ?? '',
            'primary_model_code' => $params['primary_model_code'] ?? '',
            'primary_capability' => $params['primary_capability'] ?? 'generate',
            'fallback_provider_code' => $params['fallback_provider_code'] ?? null,
            'fallback_model_code' => $params['fallback_model_code'] ?? null,
            'fallback_capability' => $params['fallback_capability'] ?? 'generate',
        ];
        $this->routes[$key] = $row;

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function routeResolve(array $params): array
    {
        $key = ($params['task'] ?? '') . '|' . ($params['complexity'] ?? '');
        if (!isset($this->routes[$key])) {
            throw new GatewayException('AIGW.ROUTE.UNKNOWN', 'Route inconnue pour cette tâche / complexité.', 422, [
                'task' => $params['task'] ?? null,
                'complexity' => $params['complexity'] ?? null,
            ]);
        }
        $route = $this->routes[$key];
        $primary = $this->modelGet([
            'provider_code' => $route['primary_provider_code'],
            'code' => $route['primary_model_code'],
            'capability' => $route['primary_capability'],
        ]);
        $fallback = null;
        if (!empty($route['fallback_provider_code']) && !empty($route['fallback_model_code'])) {
            $fallback = $this->modelGet([
                'provider_code' => $route['fallback_provider_code'],
                'code' => $route['fallback_model_code'],
                'capability' => $route['fallback_capability'],
            ]);
        }

        return [
            'route_id' => $route['id'],
            'task' => $route['task'],
            'complexity' => $route['complexity'],
            'primary' => $primary + ['kind' => $this->providers[$route['primary_provider_code']]['kind'] ?? '', 'endpoint' => $this->providers[$route['primary_provider_code']]['endpoint'] ?? '', 'secret_env_name' => $this->providers[$route['primary_provider_code']]['secret_env_name'] ?? null],
            'fallback' => $fallback === null ? null : ($fallback + ['kind' => $this->providers[$route['fallback_provider_code']]['kind'] ?? '', 'endpoint' => $this->providers[$route['fallback_provider_code']]['endpoint'] ?? '', 'secret_env_name' => $this->providers[$route['fallback_provider_code']]['secret_env_name'] ?? null]),
        ];
    }
}
