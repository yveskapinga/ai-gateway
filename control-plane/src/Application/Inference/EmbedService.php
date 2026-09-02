<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

use AiGateway\Application\Platform\RecordRunService;
use AiGateway\Application\Routing\RouteService;
use AiGateway\Domain\EmbedResult;
use AiGateway\Domain\GatewayException;

/**
 * Embed : family obligatoire, dimension = contrat du modèle SQL.
 *
 * Pourquoi : AIGW-002 INV-003 / AIGW-005. generate n’appelle pas ceci.
 *
 * Garantit : family inconnue ou mismatch → AIGW.EMBED.UNKNOWN_FAMILY.
 *
 * Ne fait pas : generate ; réindexer une app.
 *
 * Debug : route embed/low ; gateway_model.family / dimension.
 */
final class EmbedService
{
    public function __construct(
        private readonly RouteService $routes,
        private readonly RecordRunService $runs,
        private readonly EmbeddingClientLocator $clients,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function embed(array $input, string $correlationId): EmbedResult
    {
        $application = trim((string) ($input['application'] ?? ''));
        $family = trim((string) ($input['family'] ?? ''));
        $texts = $input['texts'] ?? null;
        $complexity = trim((string) ($input['complexity'] ?? 'low'));

        if ($application === '') {
            throw GatewayException::validation('application requise.');
        }
        if ($family === '') {
            throw GatewayException::validation('family obligatoire.');
        }
        if (!is_array($texts) || $texts === []) {
            throw GatewayException::validation('texts[] requis.');
        }
        /** @var list<string> $texts */
        $texts = array_values(array_map(static fn (mixed $t): string => (string) $t, $texts));

        $route = $this->routes->resolve('embed', $complexity);
        /** @var array<string, mixed> $primary */
        $primary = $route['primary'];
        $modelFamily = (string) ($primary['family'] ?? '');
        if ($modelFamily === '' || $modelFamily !== $family) {
            throw GatewayException::unknownFamily($family);
        }
        $dimension = (int) ($primary['dimension'] ?? 0);
        if ($dimension < 1) {
            throw GatewayException::validation('dimension du modèle SQL invalide.');
        }

        $kind = (string) ($primary['kind'] ?? $primary['provider_code'] ?? '');
        $model = (string) ($primary['code'] ?? '');
        $started = microtime(true);
        $vectors = $this->clients->forKind($kind)->embed($model, $texts, 30.0);
        foreach ($vectors as $vector) {
            if (count($vector) !== $dimension) {
                throw GatewayException::unavailable('Dimension du vecteur ≠ contrat SQL (' . $dimension . ').');
            }
        }

        $this->runs->append([
            'application_code' => $application,
            'task' => 'embed',
            'complexity' => $complexity,
            'provider_code' => $kind,
            'model_code' => $model,
            'status' => 'ok',
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'correlation_id' => $correlationId,
        ]);

        return new EmbedResult($vectors, $family, $dimension, $kind, $model);
    }
}
