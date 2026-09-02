<?php

declare(strict_types=1);

namespace AiGateway\ControlPlane;

/**
 * Hôte HTTP prévu (AIGW-004). P1 sert via FrontController.
 *
 * Pourquoi : calque authz — Symfony FrameworkBundle n’est pas le Domain.
 * Le Kernel documente le project dir ; l’entrée réelle est public/index.php
 * → FrontController jusqu’à un hébergement Symfony complet (optionnel).
 *
 * Ne fait pas : Voters comme politique ; generate ; embed.
 */
final class Kernel
{
    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
