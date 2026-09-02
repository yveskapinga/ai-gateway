<?php

declare(strict_types=1);

namespace AiGateway\Tests\Unit;

use AiGateway\Application\Platform\HealthService;
use AiGateway\ControlPlane\Http\Controller\HealthController;
use AiGateway\ControlPlane\Http\Exception\ExceptionListener;
use AiGateway\ControlPlane\Http\FrontController;
use AiGateway\Application\Platform\RecordRunService;
use AiGateway\Infrastructure\Inference\FakeInferenceLivenessProbe;
use AiGateway\Infrastructure\Persistence\InMemoryRoutineExecutor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P7 — fail-secure, pas de secrets, pas de noms d’apps, health vs Ollama.
 */
#[Group('P7')]
final class HardeningTest extends TestCase
{
    public function testSrcDoesNotNameConsumerApps(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $needles = ['ssk-book', 'elimu', 'normind', 'coach-app', 'coach_app'];
        $violations = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $body = (string) file_get_contents($file->getPathname());
            foreach ($needles as $needle) {
                if (stripos($body, $needle) !== false) {
                    $violations[] = $file->getFilename() . ' contains ' . $needle;
                }
            }
        }
        self::assertSame([], $violations);
    }

    public function testNoSecretMaterialInTrackedFiles(): void
    {
        $root = dirname(__DIR__, 3);
        $paths = [
            $root . '/control-plane/src',
            $root . '/control-plane/sql',
            $root . '/.env.example',
        ];
        $violations = [];
        foreach ($this->phpAndSqlFiles($paths) as $file) {
            $body = (string) file_get_contents($file);
            if (preg_match('/AIza[0-9A-Za-z_-]{20,}/', $body) === 1) {
                $violations[] = $file . ' looks like a Gemini key';
            }
            if (preg_match('/sk-[A-Za-z0-9]{20,}/', $body) === 1) {
                $violations[] = $file . ' looks like a sk- secret';
            }
        }
        self::assertSame([], $violations);
        $gitignore = (string) file_get_contents($root . '/.gitignore');
        self::assertStringContainsString('.env', $gitignore);
        self::assertFileDoesNotExist($root . '/.env');
    }

    public function testGatewayRunSchemaStillHasNoPromptColumn(): void
    {
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/schema/001_platform.sql');
        self::assertDoesNotMatchRegularExpression('/^\s*prompt\s+/mi', $sql);
        $append = (string) file_get_contents(dirname(__DIR__, 2) . '/sql/routines/aigw_run_append.sql');
        self::assertStringNotContainsString('p_prompt', $append);
    }

    public function testComposeDoesNotPublishOllama(): void
    {
        $compose = (string) file_get_contents(dirname(__DIR__, 3) . '/deployments/compose/docker-compose.yml');
        self::assertStringNotContainsString('11434', $compose);
        self::assertStringNotContainsString('ollama', strtolower($compose));
        self::assertStringContainsString('postgres:16', $compose);
    }

    public function testHealthDegradedWhenOllamaDownStillHttp200(): void
    {
        $routines = new InMemoryRoutineExecutor();
        $health = new HealthService($routines, true, new FakeInferenceLivenessProbe(false));
        $front = new FrontController(
            new HealthController($health),
            new ExceptionListener(),
            new RecordRunService($routines),
            'dev_only_change_me',
        );
        $response = $front->handle('GET', '/v1/health');

        self::assertSame(200, $response->httpStatus);
        self::assertSame('degraded', $response->body['status']);
        self::assertSame('down', $response->body['checks']['ollama']);
        self::assertSame('ok', $response->body['checks']['database']);
    }

    public function testHealthOkWhenOllamaUp(): void
    {
        $routines = new InMemoryRoutineExecutor();
        $health = new HealthService($routines, true, new FakeInferenceLivenessProbe(true));
        $status = $health->check();
        self::assertSame('ok', $status->status);
        self::assertSame('ok', $status->checks['ollama']);
    }

    public function testEmptyConfiguredApiKeyIsFailSecure(): void
    {
        $routines = new InMemoryRoutineExecutor();
        $front = new FrontController(
            new HealthController(new HealthService($routines, true)),
            new ExceptionListener(),
            new RecordRunService($routines),
            '',
        );
        $response = $front->handle('POST', '/v1/generate', ['X-API-Key' => 'anything'], [
            'application' => 'APP_DEMO',
            'complexity' => 'low',
            'prompt' => 'x',
        ]);
        self::assertSame(401, $response->httpStatus);
        self::assertSame('AIGW.AUTH.INVALID_KEY', $response->body['error']['code']);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function phpAndSqlFiles(array $paths): array
    {
        $files = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($it as $file) {
                if (in_array($file->getExtension(), ['php', 'sql', 'md', 'yml', 'example'], true) || str_ends_with($file->getFilename(), '.example')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
