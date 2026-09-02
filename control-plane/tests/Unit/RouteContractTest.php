<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Catalog\CatalogService;
use AiGateway\Application\Routing\RouteService;
use AiGateway\Domain\GatewayException;
use AiGateway\Infrastructure\Persistence\InMemoryRoutineExecutor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P3 — resolve de route depuis les données, pas depuis du PHP.
 */
#[Group('P3')]
final class RouteContractTest extends TestCase
{
    public function testLowGenerateResolvesOllamaThenGeminiFromData(): void
    {
        [$routes] = $this->seeded();
        $resolved = $routes->resolve('generate', 'low');

        self::assertSame('ollama', $resolved['primary']['provider_code']);
        self::assertSame('llama3.2:3b', $resolved['primary']['code']);
        self::assertSame('gemini', $resolved['fallback']['provider_code']);
        self::assertSame('gemini-flash-lite-latest', $resolved['fallback']['code']);
    }

    public function testUpsertChangesRouteWithoutPhpBranch(): void
    {
        [$routes] = $this->seeded();
        $before = $routes->resolve('generate', 'low');
        self::assertSame('ollama', $before['primary']['provider_code']);

        $routes->upsert('generate', 'low', 'gemini', 'gemini-flash-lite-latest', 'generate', 'ollama', 'llama3.2:3b');
        $after = $routes->resolve('generate', 'low');

        self::assertSame('gemini', $after['primary']['provider_code']);
        self::assertSame('gemini-flash-lite-latest', $after['primary']['code']);
        self::assertSame('ollama', $after['fallback']['provider_code']);

        $php = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Application/Routing/RouteService.php');
        self::assertStringNotContainsString('llama3.2', $php);
        self::assertStringNotContainsString('gemini-flash-lite-latest', $php);
        self::assertStringNotContainsString("'ollama'", $php);
    }

    public function testUnknownTaskIsBusinessError(): void
    {
        [$routes] = $this->seeded();
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Route inconnue');
        $routes->resolve('summarize', 'low');
    }

    public function testSeedSqlMapsLowToOllama(): void
    {
        $seed = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/seed/003_routes.sql');
        self::assertStringContainsString("'generate', 'low'", $seed);
        self::assertStringContainsString('ollama', $seed);
        self::assertStringContainsString('gemini-flash-lite-latest', $seed);
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/schema/003_routes.sql');
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/routines/aigw_route.sql');
    }

    /**
     * @return array{0: RouteService, 1: InMemoryRoutineExecutor}
     */
    private function seeded(): array
    {
        $routines = new InMemoryRoutineExecutor();
        $catalog = new CatalogService($routines);
        $catalog->createProvider('ollama', 'ollama', 'http://127.0.0.1:11434', null);
        $catalog->createProvider('gemini', 'gemini', 'https://generativelanguage.googleapis.com', 'GEMINI_API_KEY');
        $catalog->createModel('ollama', 'llama3.2:3b', 'generate');
        $catalog->createModel('gemini', 'gemini-flash-lite-latest', 'generate');
        $catalog->createModel('gemini', 'gemini-2.5-flash', 'generate');
        $routes = new RouteService($routines);
        $routes->upsert('generate', 'low', 'ollama', 'llama3.2:3b', 'generate', 'gemini', 'gemini-flash-lite-latest');

        return [$routes, $routines];
    }
}
