<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

interface ResourceUsage
{
    public function startTimer(string $name = 'default'): void;

    public function stopTimer(string $name = 'default'): void;

    public function getRunTimeInSeconds(string $name = 'default'): float;

    public function getFormattedPeakMemory(string $name = 'default'): string;

    public function format(string $name = 'default'): string;
}
