<?php

declare(strict_types=1);

namespace AiGateway\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * P0 — le Domain reste indépendant des vendors d’inférence et du HTTP.
 *
 * Pourquoi : AIGW-002 INV-011 / INV-012. Le fichier existe avant le runtime (P1).
 * Si Domain/ est vide, le test passe : rien à contaminer.
 * Ne fait pas : appeler un modèle.
 */
#[Group('P0')]
final class DomainIndependenceTest extends TestCase
{
    public function testDomainSourceDoesNotImportVendorInfrastructure(): void
    {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domain';
        self::assertDirectoryExists($root);

        $forbidden = [
            'use Doctrine\\',
            'use Symfony\\',
            'use Google\\',
            'use Gemini\\',
            'use PDO;',
            'new \\PDO',
        ];

        $violations = $this->scan($root, $forbidden);
        self::assertSame([], $violations);
    }

    public function testSrcDoesNotNameConsumerApps(): void
    {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($root)) {
            self::assertTrue(true);

            return;
        }

        $forbidden = ['ssk-book', 'elimu', 'normind', 'coach-app', 'coach_app'];
        $violations = $this->scan($root, $forbidden);
        self::assertSame([], $violations);
    }

    /**
     * @param list<string> $needles
     * @return list<string>
     */
    private function scan(string $root, array $needles): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            foreach ($needles as $needle) {
                if (stripos($contents, $needle) !== false) {
                    $violations[] = $file->getFilename() . ' contains ' . $needle;
                }
            }
        }

        return $violations;
    }
}
