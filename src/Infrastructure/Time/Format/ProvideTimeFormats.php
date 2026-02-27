<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

use Carbon\CarbonInterval;

trait ProvideTimeFormats
{
    public function formatDurationAsHumanString(int $timeInSeconds): string
    {
        return CarbonInterval::seconds($timeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
    }

    public function formatDurationAsClock(int $timeInSeconds): string
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

    public function formatDurationAsPaddedClock(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        $movingTime = implode(':', array_map(fn (int $value): string => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ]));

        if ($hours = $interval->hours) {
            return ltrim($hours.':'.$movingTime);
        }

        return $movingTime;
    }

    public function formatDurationAsHHMMSS(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        return implode(':', array_map(fn (int $value): string => sprintf('%02d', $value), [
            $interval->hours,
            $interval->minutes,
            $interval->seconds,
        ]));
    }
}
