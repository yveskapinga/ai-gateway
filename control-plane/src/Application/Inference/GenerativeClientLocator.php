<?php

declare(strict_types=1);

namespace AiGateway\Application\Inference;

/**
 * Choisit un client d’après `gateway_provider.kind` (donnée SQL).
 *
 * Pourquoi : pas de `if ($app === …)` ; le kind vient de la route résolue.
 * Ne fait pas : hardcoder llama / flash-lite.
 */
interface GenerativeClientLocator
{
    public function forKind(string $kind): GenerativeModelClient;
}
