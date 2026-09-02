<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Platform\InferenceLivenessProbe;

/** Fake pour tests P7 — pas de socket. */
final class FakeInferenceLivenessProbe implements InferenceLivenessProbe
{
    public function __construct(private readonly bool $up)
    {
    }

    public function ping(): bool
    {
        return $this->up;
    }
}
