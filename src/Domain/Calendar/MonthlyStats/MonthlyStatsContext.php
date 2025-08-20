<?php

declare(strict_types=1);

namespace App\Domain\Calendar\MonthlyStats;

enum MonthlyStatsContext: string
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';

    public function getUrlSlug(): string
    {
        return match ($this) {
            MonthlyStatsContext::MOVING_TIME => 'time',
            default => $this->value,
        };
    }
}
