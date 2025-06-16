<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

enum HeartRateZoneMode: string
{
    case RELATIVE = 'relative';
    case ABSOLUTE = 'absolute';
}
