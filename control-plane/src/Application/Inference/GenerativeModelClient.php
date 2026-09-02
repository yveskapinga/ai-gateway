<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

use AiGateway\Domain\GenerateResult;

/**
 * Port d’un modèle génératif (Ollama, Gemini, …).
 *
 * Pourquoi : AIGW-003. Le Domain/Application n’importe pas le SDK vendor.
 * Ne fait pas : résoudre la route.
 */
interface GenerativeModelClient
{
    /**
     * @param array<string, mixed>|null $jsonSchema
     */
    public function generate(string $model, string $prompt, ?array $jsonSchema, float $timeoutSeconds): GenerateResult;
}
