<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Inference\MapGenerativeClientLocator;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\Infrastructure\Http\FakeJsonHttpClient;
use AiGateway\Infrastructure\Inference\OllamaGenerativeClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P4 — POST /v1/generate via adapter Ollama (fake HTTP). Pas Gemini.
 */
#[Group('P4')]
final class GenerateContractTest extends TestCase
{
    public function testGenerateReturns200WithOllamaProvider(): void
    {
        $http = new FakeJsonHttpClient(static function (string $url, array $headers, array $payload, float $timeout): array {
            self::assertStringContainsString('/api/generate', $url);
            self::assertSame('llama3.2:3b', $payload['model']);
            self::assertFalse($payload['stream']);

            return ['status' => 200, 'body' => ['response' => 'bonjour depuis local'], 'error' => null];
        });
        [$front, $routines] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => new OllamaGenerativeClient($http)]),
        );

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
            'X-Correlation-Id' => 'corr-gen-0001',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'dis bonjour',
        ]);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('bonjour depuis local', $response->body['text']);
        self::assertSame('ollama', $response->body['provider']);
        self::assertSame('llama3.2:3b', $response->body['model']);
        self::assertSame('corr-gen-0001', $response->body['correlationId']);
        self::assertSame('ok', $routines->runs[0]['status']);
        self::assertSame('ollama', $routines->runs[0]['provider_code']);
        self::assertArrayNotHasKey('prompt', $routines->runs[0]);
    }

    public function testTimeoutDoesNotInventText(): void
    {
        $http = new FakeJsonHttpClient(static function (): array {
            return ['status' => 0, 'body' => null, 'error' => 'timeout'];
        });
        [$front] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => new OllamaGenerativeClient($http)]),
        );

        $response = $front->handle('POST', '/v1/generate', [
            'X-API-Key' => 'dev_only_change_me',
        ], [
            'application' => 'APP_DEMO',
            'task' => 'generate',
            'complexity' => 'low',
            'prompt' => 'x',
        ]);

        self::assertSame(503, $response->httpStatus);
        self::assertSame('AIGW.INFERENCE.UNAVAILABLE', $response->body['error']['code']);
        self::assertArrayNotHasKey('text', $response->body);
        self::assertStringNotContainsString('lorem', strtolower((string) json_encode($response->body)));
    }

    public function testDomainHasNoOllamaClient(): void
    {
        $domain = dirname(__DIR__, 2) . '/src/Domain';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($domain));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $body = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('OllamaGenerativeClient', $body);
            self::assertStringNotContainsString('11434', $body);
            self::assertStringNotContainsString('/api/generate', $body);
        }
    }
}
