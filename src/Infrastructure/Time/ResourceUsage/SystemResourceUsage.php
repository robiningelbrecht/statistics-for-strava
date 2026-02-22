<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final class SystemResourceUsage implements ResourceUsage
{
    private ?Timer $timer = null;

    private const array SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function startTimer(): void
    {
        $this->timer = new Timer();
        $this->timer->start();
    }

    public function stopTimer(): void
    {
        if (is_null($this->timer)) {
            throw new \RuntimeException('Timer not started.');
        }

        $this->timer->stop();
    }

    public function format(): string
    {
        if (is_null($this->timer)) {
            throw new \RuntimeException('Timer not started.');
        }

        return sprintf(
            'Time: %ss, Memory: %s, Peak Memory: %s',
            $this->timer->getRunTimeInSeconds(),
            $this->bytesToString(memory_get_usage(true)),
            $this->bytesToString(memory_get_peak_usage(true)),
        );
    }

    public function getRunTimeInSeconds(): float
    {
        if (is_null($this->timer)) {
            throw new \RuntimeException('Timer not started.');
        }

        return $this->timer->getRunTimeInSeconds();
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
