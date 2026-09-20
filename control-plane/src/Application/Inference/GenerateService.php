<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

use AiGateway\Application\Platform\RecordRunService;
use AiGateway\Application\Routing\RouteService;
use AiGateway\Domain\GatewayException;
use AiGateway\Domain\GenerateResult;

/**
 * Orchestre generate : route SQL → adapter → audit. P4 = voie primaire seulement.
 *
 * Pourquoi : AIGW-005 / INV-006. Timeout ≠ texte inventé.
 *
 * Garantit : en cas d’échec primary (P4) → AIGW.INFERENCE.UNAVAILABLE, pas de lorem.
 * P5 étendra en appelant le fallback de la même route.
 *
 * Ne fait pas : embed ; SQL inline ; noms d’apps.
 *
 * Debug : gateway_run.provider_code + error_code ; correlationId.
 */
final class GenerateService
{
    public function __construct(
        private readonly RouteService $routes,
        private readonly RecordRunService $runs,
        private readonly GenerativeClientLocator $clients,
        private readonly bool $useFallback = true,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function generate(array $input, string $correlationId): GenerateResult
    {
        $application = trim((string) ($input['application'] ?? ''));
        $task = trim((string) ($input['task'] ?? 'generate'));
        $complexity = trim((string) ($input['complexity'] ?? ''));
        $prompt = (string) ($input['prompt'] ?? '');
        $jsonSchema = isset($input['jsonSchema']) && is_array($input['jsonSchema']) ? $input['jsonSchema'] : null;

        if ($application === '' || $prompt === '' || $complexity === '') {
            throw GatewayException::validation('application, complexity et prompt requis.');
        }

        $route = $this->routes->resolve($task, $complexity);
        /** @var array<string, mixed> $primary */
        $primary = $route['primary'];
        $started = microtime(true);

        try {
            $result = $this->attempt($primary, $prompt, $jsonSchema);
            $this->runs->append([
                'application_code' => $application,
                'task' => $task,
                'complexity' => $complexity,
                'route_id' => $route['route_id'] ?? null,
                'provider_code' => $result->provider,
                'model_code' => $result->model,
                'status' => 'ok',
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'correlation_id' => $correlationId,
            ]);

            return $result;
        } catch (GatewayException $first) {
            if (!$this->useFallback || !isset($route['fallback']) || !is_array($route['fallback'])) {
                $this->runs->append([
                    'application_code' => $application,
                    'task' => $task,
                    'complexity' => $complexity,
                    'provider_code' => $primary['provider_code'] ?? $primary['kind'] ?? null,
                    'model_code' => $primary['code'] ?? null,
                    'status' => 'error',
                    'error_code' => $first->errorCode,
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'correlation_id' => $correlationId,
                ]);
                throw $first;
            }

            try {
                /** @var array<string, mixed> $fallback */
                $fallback = $route['fallback'];
                $result = $this->attempt($fallback, $prompt, $jsonSchema);
                $this->runs->append([
                    'application_code' => $application,
                    'task' => $task,
                    'complexity' => $complexity,
                    'route_id' => $route['route_id'] ?? null,
                    'provider_code' => $result->provider,
                    'model_code' => $result->model,
                    'status' => 'failover',
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'correlation_id' => $correlationId,
                ]);

                return $result;
            } catch (GatewayException $second) {
                $this->runs->append([
                    'application_code' => $application,
                    'task' => $task,
                    'complexity' => $complexity,
                    'status' => 'error',
                    'error_code' => 'AIGW.INFERENCE.UNAVAILABLE',
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'correlation_id' => $correlationId,
                ]);
                throw GatewayException::unavailable('Les deux voies de la route sont indisponibles.');
            }
        }
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed>|null $jsonSchema
     */
    private function attempt(array $target, string $prompt, ?array $jsonSchema): GenerateResult
    {
        $kind = (string) ($target['kind'] ?? $target['provider_code'] ?? '');
        $model = (string) ($target['code'] ?? '');
        $client = $this->clients->forKind($kind);
        // Ollama CPU ~50 s pour un prompt RAG 8192 tokens ; Gemini ensuite dans le budget PEP (150 s).
        $timeout = $kind === 'ollama' ? 80.0 : 60.0;

        return $client->generate($model, $prompt, $jsonSchema, $timeout);
    }
}
