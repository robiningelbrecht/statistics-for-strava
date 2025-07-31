<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar\MonthlyStats;

enum MonthlyStatsContext: string
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
}
