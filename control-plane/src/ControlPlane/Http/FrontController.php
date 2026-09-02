<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http;

use AiGateway\Application\Platform\RecordRunService;
use AiGateway\ControlPlane\Http\Controller\EmbedController;
use AiGateway\ControlPlane\Http\Controller\GenerateController;
use AiGateway\ControlPlane\Http\Controller\HealthController;
use AiGateway\ControlPlane\Http\Exception\ExceptionListener;
use AiGateway\ControlPlane\Http\Response\CorrelationId;
use AiGateway\ControlPlane\Http\Response\JsonEnvelope;
use AiGateway\Domain\GatewayException;

/**
 * Point d’entrée HTTP mince — routes uniquement.
 *
 * Pourquoi : AIGW-003. Controllers = endpoints ; services = validation + routines.
 * P4 : POST /v1/generate. P6 : POST /v1/embed.
 *
 * Garantit : /v1/health sans clé ; le reste de /v1 exige X-API-Key.
 *
 * Ne fait pas : SQL inline.
 *
 * Debug : method+path ; body.error.code ; X-Correlation-Id.
 */
final class FrontController
{
    public function __construct(
        private readonly HealthController $health,
        private readonly ExceptionListener $errors,
        private readonly RecordRunService $runs,
        private readonly ?string $apiKey,
        private readonly ?GenerateController $generate = null,
        private readonly ?EmbedController $embed = null,
    ) {
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $body
     */
    public function handle(string $method, string $path, array $headers = [], array $body = []): JsonEnvelope
    {
        $correlationId = CorrelationId::resolve(
            $headers['x-correlation-id'] ?? $headers['X-Correlation-Id'] ?? $headers['X-Request-Id'] ?? $headers['x-request-id'] ?? null,
        );
        $path = rtrim($path, '/') ?: '/';

        try {
            if ($method === 'GET' && $path === '/v1/health') {
                return $this->health->__invoke($correlationId);
            }

            $this->requireApiKey($headers);

            if ($method === 'POST' && $path === '/v1/generate') {
                if ($this->generate === null) {
                    throw GatewayException::notFound($path);
                }

                return $this->generate->__invoke($body, $correlationId);
            }

            if ($method === 'POST' && $path === '/v1/embed') {
                if ($this->embed === null) {
                    throw GatewayException::notFound($path);
                }

                return $this->embed->__invoke($body, $correlationId);
            }

            throw GatewayException::notFound($path);
        } catch (\Throwable $e) {
            return $this->errors->toEnvelope($e, $correlationId);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function requireApiKey(array $headers): void
    {
        $provided = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
        if (!is_string($provided) || $provided === '') {
            throw GatewayException::missingKey();
        }
        if ($this->apiKey === null || $this->apiKey === '') {
            throw GatewayException::invalidKey();
        }
        if (!hash_equals($this->apiKey, $provided)) {
            throw GatewayException::invalidKey();
        }
    }

    public function runs(): RecordRunService
    {
        return $this->runs;
    }
}
