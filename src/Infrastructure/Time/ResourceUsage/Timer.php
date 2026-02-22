<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final class Timer
{
    private ?float $timeStart = null;
    private ?float $timeStop = null;

    public function start(): void
    {
        $this->timeStart = microtime(true);
    }

    public function stop(): void
    {
        $this->timeStop = microtime(true);
    }

    public function getRunTimeInSeconds(): float
    {
        if (null === $this->timeStart) {
            throw new \RuntimeException('Timer not started.');
        }
        if (null === $this->timeStop) {
            throw new \RuntimeException('Timer not stopped.');
        }

        return round($this->timeStop - $this->timeStart, 3);
    }
}
