<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

/**
 * Port embed. Le Domain n’importe pas Gemini.
 *
 * @param list<string> $texts
 * @return list<list<float>>
 */
interface EmbeddingModelClient
{
    /**
     * @param list<string> $texts
     * @return list<list<float>>
     */
    public function embed(string $model, array $texts, float $timeoutSeconds): array;
}
