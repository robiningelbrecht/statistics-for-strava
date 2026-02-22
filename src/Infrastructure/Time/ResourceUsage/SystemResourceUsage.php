<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final class SystemResourceUsage implements ResourceUsage
{
    /** @var array<string, array{start?: float, stop: ?float, peakMemory: ?int}> */
    private array $timers = [];

    private const array SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function startTimer(string $name = 'default'): void
    {
        memory_reset_peak_usage();
        $this->timers[$name] = [
            'start' => microtime(true),
            'stop' => null,
            'peakMemory' => null,
        ];
    }

    public function stopTimer(string $name = 'default'): void
    {
        if (!isset($this->timers[$name]['start'])) {
            throw new \RuntimeException(sprintf('Timer %s not started.', $name));
        }
        $this->timers[$name]['stop'] = microtime(true);
        $this->timers[$name]['peakMemory'] = memory_get_peak_usage(true);
    }

    public function format(string $name = 'default'): string
    {
        return sprintf(
            'Time: %ss, Memory: %s, Peak Memory: %s',
            $this->getRunTimeInSeconds($name),
            self::bytesToString(memory_get_usage(true)),
            $this->getFormattedPeakMemory($name),
        );
    }

    public function getRunTimeInSeconds(string $name = 'default'): float
    {
        if (!isset($this->timers[$name]['start'])) {
            throw new \RuntimeException(sprintf('Timer %s not started.', $name));
        }

        return round($this->timers[$name]['stop'] - $this->timers[$name]['start'], 3);
    }

    public function getFormattedPeakMemory(string $name = 'default'): string
    {
        return self::bytesToString(
            $this->timers[$name]['peakMemory'] ?? memory_get_peak_usage(true),
        );
    }

    public static function bytesToString(int $bytes): string
    {
        foreach (self::SIZES as $unit => $value) {
            if ($bytes >= $value) {
                return sprintf('%.2f %s', $bytes / $value, $unit);
            }
        }

        return $bytes.' byte'.(1 !== $bytes ? 's' : '');
    }
}
