<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Catalog\CatalogService;
use AiGateway\Domain\GatewayException;
use AiGateway\Infrastructure\Persistence\InMemoryRoutineExecutor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P2 — catalogue via routines nommées. Secret = nom d’env, pas la clé.
 */
#[Group('P2')]
final class CatalogContractTest extends TestCase
{
    public function testCreateAndGetApplicationViaNamedRoutine(): void
    {
        $catalog = new CatalogService(new InMemoryRoutineExecutor());
        $created = $catalog->createApplication('APP_DEMO');
        $got = $catalog->getApplication('APP_DEMO');

        self::assertSame('APP_DEMO', $created['code']);
        self::assertSame('APP_DEMO', $got['code']);
        self::assertSame('active', $got['status']);
    }

    public function testCreateProviderStoresEnvNameNotKey(): void
    {
        $routines = new InMemoryRoutineExecutor();
        $catalog = new CatalogService($routines);
        $row = $catalog->createProvider('gemini', 'gemini', 'https://generativelanguage.googleapis.com', 'GEMINI_API_KEY');

        self::assertSame('GEMINI_API_KEY', $row['secret_env_name']);
        self::assertSame('GEMINI_API_KEY', $routines->providers['gemini']['secret_env_name']);
        self::assertStringNotContainsString('AIza', json_encode($routines->providers) ?: '');
    }

    public function testRejectsSecretValueThatIsNotEnvName(): void
    {
        $catalog = new CatalogService(new InMemoryRoutineExecutor());
        $this->expectException(GatewayException::class);
        $catalog->createProvider('gemini', 'gemini', 'https://example.invalid', 'AIzaSyFakeSecretValueNotAnEnvName');
    }

    public function testCreateAndGetModel(): void
    {
        $catalog = new CatalogService(new InMemoryRoutineExecutor());
        $catalog->createProvider('ollama', 'ollama', 'http://127.0.0.1:11434', null);
        $created = $catalog->createModel('ollama', 'llama3.2:3b', 'generate');
        $got = $catalog->getModel('ollama', 'llama3.2:3b', 'generate');

        self::assertSame('llama3.2:3b', $created['code']);
        self::assertSame('generate', $got['capability']);
        self::assertNull($got['family']);
    }

    public function testEmbedModelRequiresFamily(): void
    {
        $catalog = new CatalogService(new InMemoryRoutineExecutor());
        $catalog->createProvider('gemini', 'gemini', 'https://generativelanguage.googleapis.com', 'GEMINI_API_KEY');
        $this->expectException(GatewayException::class);
        $catalog->createModel('gemini', 'gemini-embedding-001', 'embed', null, 768);
    }

    public function testSeedSqlHasEnvNameNotKey(): void
    {
        $seed = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/seed/002_catalog.sql');
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/schema/002_catalog.sql');
        self::assertStringContainsString('GEMINI_API_KEY', $seed);
        self::assertStringContainsString('secret_env_name', $schema);
        self::assertStringNotContainsString('AIza', $seed);
        self::assertDoesNotMatchRegularExpression('/sk-[A-Za-z0-9]{10,}/', $seed);
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/routines/aigw_application.sql');
        self::assertFileExists(dirname(__DIR__, 2) . '/sql/routines/aigw_provider_model.sql');
    }
}
