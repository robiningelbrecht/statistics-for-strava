<?php

declare(strict_types=1);

namespace App\Domain\Calendar\MonthlyStats;

enum MonthlyStatsContext: string
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
}
