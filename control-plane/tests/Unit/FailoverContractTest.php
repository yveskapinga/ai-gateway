<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Inference\MapGenerativeClientLocator;
use AiGateway\Application\Routing\RouteService;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\Infrastructure\Http\FakeJsonHttpClient;
use AiGateway\Infrastructure\Inference\GeminiGenerativeClient;
use AiGateway\Infrastructure\Inference\OllamaGenerativeClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P5 — failover Gemini / Ollama sur la même route SQL.
 */
#[Group('P5')]
final class FailoverContractTest extends TestCase
{
    public function testPrimaryDownUsesFallbackAndRecordsProvider(): void
    {
        $ollama = new OllamaGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return ['status' => 0, 'body' => null, 'error' => 'timeout'];
        }));
        $gemini = new GeminiGenerativeClient(new FakeJsonHttpClient(static function (string $url): array {
            self::assertStringContainsString('generateContent', $url);

            return [
                'status' => 200,
                'body' => ['candidates' => [['content' => ['parts' => [['text' => 'reponse cloud']]]]]],
                'error' => null,
            ];
        }), 'test-key-not-a-secret-from-git');

        [$front, $routines] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => $ollama, 'gemini' => $gemini]),
            true,
        );

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
            'X-Correlation-Id' => 'corr-fail-0001',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'x',
        ]);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('gemini', $response->body['provider']);
        self::assertSame('gemini-flash-lite-latest', $response->body['model']);
        self::assertSame('reponse cloud', $response->body['text']);
        self::assertSame('failover', $routines->runs[0]['status']);
        self::assertSame('gemini', $routines->runs[0]['provider_code']);
        self::assertSame('corr-fail-0001', $routines->runs[0]['correlation_id']);
    }

    public function testBothDownReturns503WithCorrelationId(): void
    {
        $down = static fn (): array => ['status' => 0, 'body' => null, 'error' => 'timeout'];
        [$front] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator([
                'ollama' => new OllamaGenerativeClient(new FakeJsonHttpClient($down)),
                'gemini' => new GeminiGenerativeClient(new FakeJsonHttpClient($down), 'test-key'),
            ]),
            true,
        );

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
            'X-Correlation-Id' => 'corr-fail-both1',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'x',
        ]);

        self::assertSame(503, $response->httpStatus);
        self::assertSame('AIGW.INFERENCE.UNAVAILABLE', $response->body['error']['code']);
        self::assertSame('corr-fail-both1', $response->body['error']['correlationId']);
        self::assertArrayNotHasKey('text', $response->body);
    }

    public function testGemini429FailsOverToOllama(): void
    {
        $gemini = new GeminiGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return ['status' => 429, 'body' => null, 'error' => 'rate limit'];
        }), 'test-key');
        $ollama = new OllamaGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return ['status' => 200, 'body' => ['response' => 'local after 429'], 'error' => null];
        }));

        [$front, $routines] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => $ollama, 'gemini' => $gemini]),
            true,
        );
        $routes = new RouteService($routines);
        $routes->upsert('generate', 'low', 'gemini', 'gemini-flash-lite-latest', 'generate', 'ollama', 'llama3.2:3b');

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'x',
        ]);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('ollama', $response->body['provider']);
        self::assertSame('local after 429', $response->body['text']);
        self::assertSame('failover', $routines->runs[0]['status']);
    }

    public function testPlaceholderStructuredAnswerFailsOverToGemini(): void
    {
        $schema = [
            'type' => 'object',
            'required' => ['answer', 'sourceIds', 'insufficientEvidence'],
        ];
        $ollama = new OllamaGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return [
                'status' => 200,
                'body' => ['response' => '{"answer":"nous n\'avons pas reçu d\'answer","sourceIds":[],"insufficientEvidence":true}'],
                'error' => null,
            ];
        }));
        $gemini = new GeminiGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return [
                'status' => 200,
                'body' => ['candidates' => [['content' => ['parts' => [['text' => '{"answer":"Kapinga Kadima Luse Naomi","sourceIds":["s1"],"insufficientEvidence":false}']]]]]],
                'error' => null,
            ];
        }), 'test-key');

        [$front, $routines] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => $ollama, 'gemini' => $gemini]),
            true,
        );

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'x',
            'jsonSchema' => $schema,
        ]);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('gemini', $response->body['provider']);
        self::assertSame('Kapinga Kadima Luse Naomi', $response->body['json']['answer']);
        self::assertSame('failover', $routines->runs[0]['status']);
    }
}
