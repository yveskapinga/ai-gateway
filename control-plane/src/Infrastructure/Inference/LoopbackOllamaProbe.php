<?php

declare(strict_types=1);

namespace AiGateway\Infrastructure\Inference;

use AiGateway\Application\Platform\InferenceLivenessProbe;

/**
 * Ping TCP loopback uniquement (127.0.0.1). Jamais 0.0.0.0.
 *
 * Pourquoi : P7 — health degraded si Ollama down, process toujours vivant.
 * Ne fait pas : bind public ; nginx /ollama.
 */
final class LoopbackOllamaProbe implements InferenceLivenessProbe
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 11434,
        private readonly float $timeoutSeconds = 0.2,
    ) {
    }

    public function ping(): bool
    {
        if ($this->host !== '127.0.0.1' && $this->host !== 'localhost') {
            return false;
        }
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeoutSeconds);
        if (!is_resource($fp)) {
            return false;
        }
        fclose($fp);

        return true;
    }
}
