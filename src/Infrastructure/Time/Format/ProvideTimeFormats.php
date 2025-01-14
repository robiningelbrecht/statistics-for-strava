<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

use Carbon\CarbonInterval;

trait ProvideTimeFormats
{
    public function formatDurationForHumans(int $timeInSeconds, bool $trimLeadingZeros = true): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        if (!$trimLeadingZeros) {
            return implode(':', array_map(fn (int $value) => sprintf('%02d', $value), [
                $interval->hours,
                $interval->minutes,
                $interval->seconds,
            ]));
        }

        if (!$interval->minutes && !$interval->hours) {
            return $interval->seconds.'s';
        }

        $movingTime = implode(':', array_map(fn (int $value) => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ]));

        if ($hours = $interval->hours) {
            $movingTime = $hours.':'.$movingTime;
        }

        return ltrim($movingTime, '0');
    }
}
