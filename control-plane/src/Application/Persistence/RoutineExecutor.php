<?php

declare(strict_types=1);

namespace AiGateway\Application\Persistence;

/**
 * Port d’appel des routines SQL nommées.
 *
 * Pourquoi : ADR-001. Les services ne contiennent pas de SQL inline.
 *
 * Garantit : seul un nom whitelisté `aigw_*` peut être appelé.
 *
 * Ne fait pas : valider le métier generate/embed ; choisir le modèle (c’est SQL P3).
 *
 * Debug : logger le nom de routine + correlation_id ; ouvrir control-plane/sql/routines/.
 *
 * @param array<string, mixed> $params
 */
interface RoutineExecutor
{
    public function call(string $routine, array $params = []): mixed;
}
