<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time\Sleep;

use App\Infrastructure\Time\Sleep;

class NullSleep implements Sleep
{
    private int $totalSleptInSeconds = 0;

    public function sweetDreams(int $durationInSeconds): void
    {
        $this->totalSleptInSeconds += $durationInSeconds;
    }

    public function getTotalSleptInSeconds(): int
    {
        return $this->totalSleptInSeconds;
    }
}
