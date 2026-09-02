<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

interface EmbeddingClientLocator
{
    public function forKind(string $kind): EmbeddingModelClient;
}
