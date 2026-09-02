<?php

declare(strict_types=1);

namespace AiGateway\Application\Catalog;

use AiGateway\Application\Persistence\RoutineExecutor;
use AiGateway\Domain\GatewayException;

/**
 * Catalogue application / provider / model via routines nommées.
 *
 * Pourquoi : AIGW-002 INV-004 / INV-009. Le PHP ne choisit pas le modèle.
 *
 * Garantit : secret_env_name ressemble à un nom d’env, pas à une clé.
 *
 * Ne fait pas : résoudre une route (P3) ; appeler un LLM.
 *
 * Debug : aigw_provider_list() ; colonne secret_env_name.
 */
final class CatalogService
{
    public function __construct(private readonly RoutineExecutor $routines)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function createApplication(string $code, string $status = 'active'): array
    {
        $code = trim($code);
        if ($code === '') {
            throw GatewayException::validation('code application requis.');
        }

        return $this->requireRow($this->routines->call('aigw_application_create', [
            'code' => $code,
            'status' => $status,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function getApplication(string $code): array
    {
        return $this->requireRow($this->routines->call('aigw_application_get', ['code' => $code]));
    }

    /**
     * @return array<string, mixed>
     */
    public function createProvider(string $code, string $kind, string $endpoint, ?string $secretEnvName): array
    {
        $code = trim($code);
        if ($code === '' || trim($kind) === '' || trim($endpoint) === '') {
            throw GatewayException::validation('provider code, kind et endpoint requis.');
        }
        if ($secretEnvName !== null && $secretEnvName !== '') {
            if (preg_match('/^[A-Z][A-Z0-9_]{2,63}$/', $secretEnvName) !== 1) {
                throw GatewayException::validation('secret_env_name doit être un nom d’environnement, pas une clé.');
            }
        }

        return $this->requireRow($this->routines->call('aigw_provider_create', [
            'code' => $code,
            'kind' => $kind,
            'endpoint' => $endpoint,
            'secret_env_name' => $secretEnvName,
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listProviders(): array
    {
        $list = $this->routines->call('aigw_provider_list');
        if (!is_array($list)) {
            throw GatewayException::internal();
        }

        /** @var list<array<string, mixed>> $list */
        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    public function createModel(
        string $providerCode,
        string $code,
        string $capability,
        ?string $family = null,
        ?int $dimension = null,
        float $relativeCost = 1.0,
    ): array {
        if (!in_array($capability, ['generate', 'embed'], true)) {
            throw GatewayException::validation('capability generate|embed requise.');
        }
        if ($capability === 'embed' && ($family === null || trim($family) === '')) {
            throw GatewayException::validation('family obligatoire pour capability=embed.');
        }

        return $this->requireRow($this->routines->call('aigw_model_create', [
            'provider_code' => $providerCode,
            'code' => $code,
            'capability' => $capability,
            'family' => $family,
            'dimension' => $dimension,
            'relative_cost' => $relativeCost,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function getModel(string $providerCode, string $code, string $capability): array
    {
        return $this->requireRow($this->routines->call('aigw_model_get', [
            'provider_code' => $providerCode,
            'code' => $code,
            'capability' => $capability,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function requireRow(mixed $row): array
    {
        if (!is_array($row)) {
            throw GatewayException::internal();
        }

        /** @var array<string, mixed> $row */
        return $row;
    }
}
