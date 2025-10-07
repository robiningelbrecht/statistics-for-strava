<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

use Carbon\CarbonInterval;

trait ProvideTimeFormats
{
    public function formatDurationForHumans(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        if (!$interval->minutes && !$interval->hours) {
            return $interval->seconds.'s';
        }

        $movingTime = implode(':', array_map(fn (int $value): string => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ]));

        if ($hours = $interval->hours) {
            $movingTime = $hours.':'.$movingTime;
        }

        return ltrim($movingTime, '0');
    }

    public function formatDurationForChartLabel(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        $movingTime = implode(':', array_map(fn (int $value): string => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ]));

        if ($hours = $interval->hours) {
            $movingTime = ltrim($hours.':'.$movingTime);
        }

        return $movingTime;
    }

    public function formatDurationForHumansWithoutTrimming(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        return implode(':', array_map(fn (int $value): string => sprintf('%02d', $value), [
            $interval->hours,
            $interval->minutes,
            $interval->seconds,
        ]));
    }
}
