<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final class SystemResourceUsage implements ResourceUsage
{
    private ?float $timeStart = null;
    private ?float $timeStop = null;

    private const array SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function startTimer(): void
    {
        $this->timeStart = microtime(true);
        $this->timeStop = null;
    }

    public function stopTimer(): void
    {
        $this->timeStop = microtime(true);
    }

    public function format(): string
    {
        return sprintf(
            'Time: %ss, Memory: %s, Peak Memory: %s',
            $this->getRunTimeInSeconds(),
            $this->bytesToString(memory_get_usage(true)),
            $this->bytesToString(memory_get_peak_usage(true))
        );
    }

    public function getRunTimeInSeconds(): float
    {
        return round($this->timeStop - $this->timeStart, 3);
    }

    public function bytesToString(int $bytes): string
    {
        foreach (self::SIZES as $unit => $value) {
            if ($bytes >= $value) {
                return sprintf('%.2f %s', $bytes / $value, $unit);
            }
        }

        return $bytes.' byte'.(1 !== $bytes ? 's' : '');
    }
}
