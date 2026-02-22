<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Daemon;

use App\Infrastructure\Daemon\ProcessForker;

final readonly class SequentialProcessForker implements ProcessForker
{
    public function fork(): int
    {
        return -1;
    }

    public function waitPid(int $pid, int &$status): int
    {
        return -1;
    }
}
