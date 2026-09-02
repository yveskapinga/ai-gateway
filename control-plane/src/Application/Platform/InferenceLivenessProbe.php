<?php

declare(strict_types=1);

namespace AiGateway\Application\Platform;

/**
 * Sonde Ollama loopback — liveness d’inférence locale, pas une route.
 *
 * Pourquoi : AIGW-005 P7. Health distingue config OK vs Ollama down.
 * Ne fait pas : generate ; exposer Ollama sur une IP publique.
 */
interface InferenceLivenessProbe
{
    public function ping(): bool;
}
