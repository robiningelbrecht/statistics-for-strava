<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

/**
 * @codeCoverageIgnore
 */
final readonly class PcntlProcessForker implements ProcessForker
{
    public function fork(): int
    {
        return pcntl_fork();
    }

    public function waitPid(int $pid, int &$status): int
    {
        return pcntl_waitpid($pid, $status);
    }
}
