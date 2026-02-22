<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon;

interface ProcessForker
{
    /**
     * @return int -1 on failure, 0 in child process, >0 (child pid) in parent process
     */
    public function fork(): int;

    public function waitPid(int $pid, int &$status): int;
}
