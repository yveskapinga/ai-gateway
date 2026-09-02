<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane\Http;

use AiGateway\Application\Catalog\CatalogService;
use AiGateway\Application\Inference\EmbedService;
use AiGateway\Application\Inference\EmbeddingClientLocator;
use AiGateway\Application\Inference\GenerateService;
use AiGateway\Application\Inference\GenerativeClientLocator;
use AiGateway\Application\Platform\HealthService;
use AiGateway\Application\Platform\RecordRunService;
use AiGateway\Application\Routing\RouteService;
use AiGateway\ControlPlane\Http\Controller\EmbedController;
use AiGateway\ControlPlane\Http\Controller\GenerateController;
use AiGateway\ControlPlane\Http\Controller\HealthController;
use AiGateway\ControlPlane\Http\Exception\ExceptionListener;
use AiGateway\Infrastructure\Persistence\InMemoryRoutineExecutor;

/**
 * Câblage du control plane (tests et hôte mince).
 *
 * Pourquoi : AIGW-003 — controllers minces, services, routines. Pas de SQL ici.
 *
 * Ne fait pas : contenir le SDK (il vit dans sdk/php).
 */
final class ControlPlaneFactory
{
    public static function inMemoryFrontController(?string $apiKey = 'dev_only_change_me'): FrontController
    {
        $routines = new InMemoryRoutineExecutor();
        $health = new HealthService($routines, true);
        $runs = new RecordRunService($routines);

        return new FrontController(
            new HealthController($health),
            new ExceptionListener(),
            $runs,
            $apiKey,
        );
    }

    /**
     * @return array{0: FrontController, 1: InMemoryRoutineExecutor}
     */
    public static function inMemoryGenerateFrontController(
        GenerativeClientLocator $locator,
        bool $useFallback = true,
        ?string $apiKey = 'dev_only_change_me',
        ?EmbeddingClientLocator $embedLocator = null,
    ): array {
        $routines = new InMemoryRoutineExecutor();
        $catalog = new CatalogService($routines);
        $catalog->createProvider('ollama', 'ollama', 'http://127.0.0.1:11434', null);
        $catalog->createProvider('gemini', 'gemini', 'https://generativelanguage.googleapis.com', 'GEMINI_API_KEY');
        $catalog->createModel('ollama', 'llama3.2:3b', 'generate');
        $catalog->createModel('gemini', 'gemini-flash-lite-latest', 'generate');
        $catalog->createModel('gemini', 'gemini-embedding-001', 'embed', 'gemini-768', 768);
        $routes = new RouteService($routines);
        $routes->upsert('generate', 'low', 'ollama', 'llama3.2:3b', 'generate', 'gemini', 'gemini-flash-lite-latest');
        $routes->upsert('embed', 'low', 'gemini', 'gemini-embedding-001', 'embed');
        $runs = new RecordRunService($routines);
        $generate = new GenerateService($routes, $runs, $locator, $useFallback);

        $embedController = null;
        if ($embedLocator !== null) {
            $embedController = new EmbedController(new EmbedService($routes, $runs, $embedLocator));
        }

        $front = new FrontController(
            new HealthController(new HealthService($routines, true)),
            new ExceptionListener(),
            $runs,
            $apiKey,
            new GenerateController($generate),
            $embedController,
        );

        return [$front, $routines];
    }
}
