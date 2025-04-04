<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

enum IntervalUnit: string
{
    case KILOMETERS = 'km';
    case MILES = 'mi';
    case HOURS = 'hours';
    case DAYS = 'days';
    case WEEKS = 'weeks';
    case MONTHS = 'months';
}
