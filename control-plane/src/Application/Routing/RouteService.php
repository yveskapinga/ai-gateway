<?php

declare(strict_types=1);

namespace AiGateway\Application\Routing;

use AiGateway\Application\Persistence\RoutineExecutor;
use AiGateway\Domain\GatewayException;

/**
 * Résout une route d’inférence — données SQL, pas de if métier.
 *
 * Pourquoi : AIGW-002 INV-004. complexity low|medium|high vit dans gateway_route.
 *
 * Garantit : task inconnue → AIGW.ROUTE.UNKNOWN. Aucun branchement PHP sur 'low'.
 *
 * Ne fait pas : appeler Ollama/Gemini (P4/P5).
 *
 * Debug : aigw_route_resolve(task, complexity).
 */
final class RouteService
{
    public function __construct(private readonly RoutineExecutor $routines)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $task, string $complexity): array
    {
        $task = trim($task);
        $complexity = trim($complexity);
        if ($task === '' || !in_array($complexity, ['low', 'medium', 'high'], true)) {
            throw GatewayException::validation('task et complexity (low|medium|high) requis.');
        }

        $row = $this->routines->call('aigw_route_resolve', [
            'task' => $task,
            'complexity' => $complexity,
        ]);
        if (!is_array($row)) {
            throw GatewayException::internal();
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function upsert(
        string $task,
        string $complexity,
        string $primaryProvider,
        string $primaryModel,
        string $primaryCapability = 'generate',
        ?string $fallbackProvider = null,
        ?string $fallbackModel = null,
        string $fallbackCapability = 'generate',
    ): array {
        $row = $this->routines->call('aigw_route_upsert', [
            'task' => $task,
            'complexity' => $complexity,
            'primary_provider_code' => $primaryProvider,
            'primary_model_code' => $primaryModel,
            'primary_capability' => $primaryCapability,
            'fallback_provider_code' => $fallbackProvider,
            'fallback_model_code' => $fallbackModel,
            'fallback_capability' => $fallbackCapability,
        ]);
        if (!is_array($row)) {
            throw GatewayException::internal();
        }

        /** @var array<string, mixed> $row */
        return $row;
    }
}
