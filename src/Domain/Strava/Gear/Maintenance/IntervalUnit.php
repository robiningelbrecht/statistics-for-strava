<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

enum IntervalUnit: string
{
    case EVERY_X_KILOMETERS_USED = 'km';
    case EVERY_X_MILES_USED = 'mi';
    case EVERY_X_HOURS_USED = 'hours';
    case EVERY_X_DAYS = 'days';
}
