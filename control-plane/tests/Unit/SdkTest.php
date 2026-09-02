<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Inference\MapEmbeddingClientLocator;
use AiGateway\Application\Inference\MapGenerativeClientLocator;
use AiGateway\ControlPlane\Http\ControlPlaneFactory;
use AiGateway\ControlPlane\Http\FrontController;
use AiGateway\Infrastructure\Http\FakeJsonHttpClient;
use AiGateway\Infrastructure\Inference\FakeEmbeddingClient;
use AiGateway\Infrastructure\Inference\OllamaGenerativeClient;
use AiGateway\Sdk\CallableTransport;
use AiGateway\Sdk\GatewayClient;
use AiGateway\Sdk\GatewayClientException;
use AiGateway\Sdk\TransportException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P8 SDK PEP AIGW-006 — PEP asks, gateway decides ; transport ≠ texte inventé.
 */
#[Group('P8')]
final class SdkTest extends TestCase
{
    public function testGenerateAsksGatewayAndReturnsRealProvider(): void
    {
        $client = $this->client($this->front());
        $out = $client->generate('APP_DEMO', 'generate', 'low', 'bonjour', null, 'corr-sdk-gen-01');

        self::assertSame('bonjour depuis local', $out->text);
        self::assertSame('ollama', $out->provider);
        self::assertSame('llama3.2:3b', $out->model);
        self::assertSame('corr-sdk-gen-01', $out->correlationId);
    }

    public function testEmbedAsksGatewayWithFamily(): void
    {
        $client = $this->client($this->front());
        $out = $client->embed('APP_DEMO', 'gemini-768', ['alpha']);

        self::assertSame('gemini-768', $out->family);
        self::assertSame(768, $out->dimension);
        self::assertCount(1, $out->vectors);
        self::assertCount(768, $out->vectors[0]);
    }

    public function testUnknownFamilyIsGatewayErrorNotInventedVector(): void
    {
        $client = $this->client($this->front());
        try {
            $client->embed('APP_DEMO', 'minilm-384', ['x']);
            self::fail('expected GatewayClientException');
        } catch (GatewayClientException $e) {
            self::assertSame('AIGW.EMBED.UNKNOWN_FAMILY', $e->errorCode);
            self::assertSame(422, $e->httpStatus);
        }
    }

    public function testUnavailableIsNotInventedText(): void
    {
        $http = new FakeJsonHttpClient(static function (): array {
            return ['status' => 0, 'body' => null, 'error' => 'timeout'];
        });
        [$front] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => new OllamaGenerativeClient($http)]),
            true,
        );
        $client = $this->client($front);
        try {
            $client->generate('APP_DEMO', 'generate', 'low', 'x');
            self::fail('expected GatewayClientException');
        } catch (GatewayClientException $e) {
            self::assertSame('AIGW.INFERENCE.UNAVAILABLE', $e->errorCode);
            self::assertSame(503, $e->httpStatus);
            self::assertNotEmpty($e->correlationId);
        }
    }

    public function testTransportTimeoutNeverReturnsText(): void
    {
        $client = new GatewayClient(new CallableTransport(static function (): array {
            return ['status' => 0, 'body' => null, 'correlationId' => null, 'error' => 'timeout'];
        }), 'dev_only_change_me');

        $this->expectException(TransportException::class);
        $client->generate('APP_DEMO', 'generate', 'low', 'x');
    }

    public function testSdkHasNoLocalEngineAndNoVendorSecrets(): void
    {
        $root = dirname(__DIR__, 3) . '/sdk/php/src';
        self::assertDirectoryExists($root);
        $engines = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (str_ends_with($file->getFilename(), 'Engine.php')) {
                $engines[] = $file->getFilename();
            }
            $body = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('GEMINI_API_KEY', $body);
            self::assertStringNotContainsString('11434', $body);
            self::assertStringNotContainsString('aigw_route_resolve', $body);
            self::assertStringNotContainsString('use Google\\', $body);
            self::assertStringNotContainsString('OllamaGenerativeClient', $body);
            self::assertStringNotContainsString('ssk-book', $body);
        }
        self::assertSame([], $engines);
        self::assertFileExists(dirname(__DIR__, 3) . '/docs/contracts/AIGW-006-sdk.md');
    }

    public function testFromEnvironmentReadsGatewayKeyNotGemini(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__, 3) . '/sdk/php/src/GatewayClient.php');
        self::assertStringContainsString('AIGW_API_KEY', $src);
        self::assertStringContainsString('AIGW_BASE_URL', $src);
        self::assertStringNotContainsString('getenv(\'GEMINI', $src);
    }

    private function front(): FrontController
    {
        $ollama = new OllamaGenerativeClient(new FakeJsonHttpClient(static function (): array {
            return ['status' => 200, 'body' => ['response' => 'bonjour depuis local'], 'error' => null];
        }));
        [$front] = ControlPlaneFactory::inMemoryGenerateFrontController(
            new MapGenerativeClientLocator(['ollama' => $ollama]),
            true,
            'dev_only_change_me',
            new MapEmbeddingClientLocator(['gemini' => new FakeEmbeddingClient(768)]),
        );

        return $front;
    }

    private function client(FrontController $front): GatewayClient
    {
        return new GatewayClient(new CallableTransport(
            static function (string $method, string $path, array $headers, array $body) use ($front): array {
                $env = $front->handle($method, $path, $headers, $body);

                return [
                    'status' => $env->httpStatus,
                    'body' => $env->body,
                    'correlationId' => $env->correlationId,
                    'error' => null,
                ];
            },
        ), 'dev_only_change_me');
    }
}
