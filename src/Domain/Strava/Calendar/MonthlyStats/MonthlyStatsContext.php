<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar\MonthlyStats;

enum MonthlyStatsContext: string
{
    case TIME = 'time';
    case DISTANCE = 'distance';
}
