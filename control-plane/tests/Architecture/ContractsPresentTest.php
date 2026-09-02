<?php

declare(strict_types=1);

namespace AiGateway\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P0 — les contrats et règles existent avant tout runtime.
 *
 * Pourquoi : AIGW-001. Un agent ne doit pas inventer une architecture parallèle.
 * Ne fait pas : démarrer HTTP, Postgres, Ollama.
 */
#[Group('P0')]
final class ContractsPresentTest extends TestCase
{
    public function testNormativeContractsExist(): void
    {
        $root = dirname(__DIR__, 3);
        foreach ([
            'docs/contracts/AIGW-001-master.md',
            'docs/contracts/AIGW-002-invariants.md',
            'docs/contracts/AIGW-003-architecture.md',
            'docs/contracts/AIGW-004-stack.md',
            'docs/contracts/AIGW-005-api.md',
            'docs/governance/MASTER-INSTRUCTION.md',
            'docs/governance/IMPLEMENTATION-ROADMAP.md',
            'docs/adr/ADR-001-sql-first-and-api-contract.md',
            'AGENTS.md',
            'scripts/check-sql-first-boundaries.sh',
            '.cursor/rules/sql-first.mdc',
            '.cursor/rules/phase-gates.mdc',
            '.cursor/rules/no-secrets.mdc',
            '.cursor/rules/no-consumer-apps.mdc',
        ] as $relative) {
            self::assertFileExists($root . '/' . $relative, $relative);
        }
    }

    public function testRoadmapContainsAutonomousLoop(): void
    {
        $roadmap = (string) file_get_contents(dirname(__DIR__, 3) . '/docs/governance/IMPLEMENTATION-ROADMAP.md');
        self::assertStringContainsString('enchaîner IMMÉDIATEMENT N+1', $roadmap);
        self::assertStringContainsString('P7 PASS', $roadmap);
    }

    public function testInvariantsForbidConsumerNamesInDomain(): void
    {
        $invariants = (string) file_get_contents(dirname(__DIR__, 3) . '/docs/contracts/AIGW-002-invariants.md');
        self::assertStringContainsString('INV-012', $invariants);
        self::assertStringContainsString('family', $invariants);
    }
}
