<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Persistence;

/**
 * PDO Postgres — transporteur, pas un modèle objet (ADR-001).
 *
 * Pourquoi : AIGW-004. Les services n’ouvrent pas de connexion eux-mêmes.
 * Ne fait pas : SQL métier ; stocker des clés.
 */
final class PostgresConnectionFactory
{
    public static function fromEnvironment(): ?\PDO
    {
        $host = getenv('POSTGRES_HOST') ?: '';
        if ($host === '') {
            return null;
        }
        $port = getenv('POSTGRES_PORT') ?: '5432';
        $db = getenv('POSTGRES_DB') ?: 'aigateway';
        $user = getenv('POSTGRES_USER') ?: 'aigateway';
        $password = getenv('POSTGRES_PASSWORD') ?: '';
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
        $pdo = new \PDO($dsn, $user, $password);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
