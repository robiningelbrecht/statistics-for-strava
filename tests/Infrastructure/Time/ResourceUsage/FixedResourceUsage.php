<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\ResourceUsage;

final readonly class FixedResourceUsage implements ResourceUsage
{
    public function startTimer(string $name = 'default'): void
    {
    }

    public function stopTimer(string $name = 'default'): void
    {
    }

    public function getRunTimeInSeconds(string $name = 'default'): float
    {
        return 10;
    }

    public function getFormattedPeakMemory(string $name = 'default'): string
    {
        return '45.00 MB';
    }

    public function format(string $name = 'default'): string
    {
        return 'Time: 10s, Memory: 45MB';
    }
}
