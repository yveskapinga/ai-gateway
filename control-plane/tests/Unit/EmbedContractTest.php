<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Inference\MapEmbeddingClientLocator;
use AiGateway\Application\Inference\MapGenerativeClientLocator;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\Infrastructure\Http\FakeJsonHttpClient;
use AiGateway\Infrastructure\Inference\FakeEmbeddingClient;
use AiGateway\Infrastructure\Inference\OllamaGenerativeClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P6 — POST /v1/embed, family obligatoire, pas de mélange.
 */
#[Group('P6')]
final class EmbedContractTest extends TestCase
{
    public function testEmbedReturnsVectorsMatchingSqlDimension(): void
    {
        [$front] = $this->front();
        $response = $front->handle('POST', '/v1/embed', [
            'X-API-Key' => 'dev_only_change_me',
            'X-Correlation-Id' => 'corr-emb-0001',
        ], [
            'application' => 'APP_DEMO',
            'family' => 'gemini-768',
            'texts' => ['alpha', 'beta'],
        ]);

        self::assertSame(200, $response->httpStatus);
        self::assertSame('gemini-768', $response->body['family']);
        self::assertSame(768, $response->body['dimension']);
        self::assertCount(2, $response->body['vectors']);
        self::assertCount(768, $response->body['vectors'][0]);
        self::assertSame('gemini', $response->body['provider']);
        self::assertSame('gemini-embedding-001', $response->body['model']);
    }

    public function testUnknownFamilyIsBusinessError(): void
    {
        [$front] = $this->front();
        $response = $front->handle('POST', '/v1/embed', [
            'X-API-Key' => 'dev_only_change_me',
        ], [
            'application' => 'APP_DEMO',
            'family' => 'minilm-384',
            'texts' => ['x'],
        ]);

        self::assertSame(422, $response->httpStatus);
        self::assertSame('AIGW.EMBED.UNKNOWN_FAMILY', $response->body['error']['code']);
    }

    public function testMissingFamilyIsValidationError(): void
    {
        [$front] = $this->front();
        $response = $front->handle('POST', '/v1/embed', [
            'X-API-Key' => 'dev_only_change_me',
        ], [
            'application' => 'APP_DEMO',
            'texts' => ['x'],
        ]);

        self::assertSame(422, $response->httpStatus);
        self::assertSame('AIGW.VALIDATION.FAILED', $response->body['error']['code']);
    }

    public function testGenerateDoesNotCallEmbed(): void
    {
        $generateSrc = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Application/Inference/GenerateService.php');
        self::assertStringNotContainsString('EmbedService', $generateSrc);
        self::assertStringNotContainsString('EmbeddingModelClient', $generateSrc);
        self::assertStringNotContainsString('batchEmbedContents', $generateSrc);
    }

    /**
     * @return array{0: \AiGateway\ControlPlane\Http\FrontController}
     */
    private function front(): array
    {
        $ollama = new OllamaGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return ['status' => 200, 'body' => ['response' => 'ok'], 'error' => null];
        }));

        return ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => $ollama]),
            true,
            'dev_only_change_me',
            new MapEmbeddingClientLocator(['gemini' => new FakeEmbeddingClient(768)]),
        );
    }
}
