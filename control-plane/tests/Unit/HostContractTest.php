<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Platform\HealthService;
use AiGateway\Application\Platform\RecordRunService;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\ControlPlane\Http\Exception\ExceptionListener;
use AiGateway\ControlPlane\Http\Response\JsonEnvelope;
use AiGateway\Domain\GatewayException;
use AiGateway\Infrastructure\Persistence\InMemoryRoutineExecutor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P1 — hôte HTTP, contrat d’erreur, audit append (pas d’inférence).
 */
#[Group('P1')]
final class HostContractTest extends TestCase
{
    public function testHealthReturns200WithCorrelationId(): void
    {
        $front = ControlPlaneFactory::inMemoryFrontController();
        $response = $front->handle('GET', '/v1/health', ['X-Correlation-Id' => 'corr-health-01']);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('ok', $response->body['status']);
        self::assertSame('AIGW.PLATFORM.HEALTH_OK', $response->body['code']);
        self::assertSame('corr-health-01', $response->body['correlationId']);
        self::assertSame('ok', $response->body['checks']['database']);
    }

    public function testHealthDoesNotRequireApiKey(): void
    {
        $front = ControlPlaneFactory::inMemoryFrontController();
        $response = $front->handle('GET', '/v1/health');

        self::assertSame(200, $response->httpStatus);
        self::assertArrayNotHasKey('error', $response->body);
    }

    public function testMissingApiKeyReturnsJsonBusinessError(): void
    {
        $front = ControlPlaneFactory::inMemoryFrontController();
        $response = $front->handle('POST', '/v1/generate', ['X-Correlation-Id' => 'corr-auth-01'], ['prompt' => 'x']);

        self::assertSame(401, $response->httpStatus);
        self::assertSame('AIGW.AUTH.MISSING_KEY', $response->body['error']['code']);
        self::assertSame('corr-auth-01', $response->body['error']['correlationId']);
        self::assertArrayHasKey('message', $response->body['error']);
        self::assertArrayHasKey('details', $response->body['error']);
        self::assertSame(['code', 'message', 'details', 'correlationId'], array_keys($response->body['error']));
    }

    public function testInvalidApiKeyReturnsJsonBusinessError(): void
    {
        $front = ControlPlaneFactory::inMemoryFrontController();
        $response = $front->handle('POST', '/v1/generate', ['X-API-Key' => 'wrong']);

        self::assertSame(401, $response->httpStatus);
        self::assertSame('AIGW.AUTH.INVALID_KEY', $response->body['error']['code']);
        self::assertNotEmpty($response->body['error']['correlationId']);
    }

    public function testUnknownPathWithKeyIsNotFoundNotInference(): void
    {
        $front = ControlPlaneFactory::inMemoryFrontController();
        $response = $front->handle('POST', '/v1/does-not-exist', ['X-API-Key' => 'dev_only_change_me']);

        self::assertSame(404, $response->httpStatus);
        self::assertSame('AIGW.COMMON.NOT_FOUND', $response->body['error']['code']);
    }

    public function testListenerHidesInternalThrowable(): void
    {
        $listener = new ExceptionListener();
        $envelope = $listener->toEnvelope(new \RuntimeException('secret connection string'), 'corr-12345678');

        self::assertSame(500, $envelope->httpStatus);
        self::assertSame('AIGW.COMMON.INTERNAL', $envelope->body['error']['code']);
        self::assertStringNotContainsString('secret', (string) json_encode($envelope->body));
    }

    public function testHealthDegradedWhenDatabaseNotConfigured(): void
    {
        $service = new HealthService(null, false);
        $status = $service->check();

        self::assertSame('degraded', $status->status);
        self::assertSame('AIGW.PLATFORM.HEALTH_DEGRADED', $status->code);
    }

    public function testRunAppendRejectsPromptField(): void
    {
        $service = new RecordRunService(new InMemoryRoutineExecutor());
        $this->expectException(GatewayException::class);
        $service->append(['correlation_id' => 'corr-12345678', 'prompt' => 'never store me']);
    }

    public function testRunAppendStoresWithoutPrompt(): void
    {
        $routines = new InMemoryRoutineExecutor();
        $service = new RecordRunService($routines);
        $row = $service->append([
            'correlation_id' => 'corr-12345678',
            'status' => 'ok',
            'task' => 'generate',
        ]);

        self::assertSame('corr-12345678', $row['correlation_id']);
        self::assertArrayNotHasKey('prompt', $row);
        self::assertCount(1, $routines->runs);
    }

    public function testSchemaHasNoPromptColumn(): void
    {
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/schema/001_platform.sql');
        self::assertStringContainsString('gateway_run', $sql);
        self::assertDoesNotMatchRegularExpression('/^\s*prompt\s+/mi', $sql);
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/routines/aigw_run_append.sql');
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/routines/aigw_health_ping.sql');
    }

    public function testErrorEnvelopeShape(): void
    {
        $envelope = JsonEnvelope::error('AIGW.AUTH.MISSING_KEY', 'Clé d’API requise.', 'corr-abcd1234', 401);
        self::assertSame(['error'], array_keys($envelope->body));
        self::assertSame(['code', 'message', 'details', 'correlationId'], array_keys($envelope->body['error']));
    }
}
