<?php

declare(strict_types=1);

namespace App\Domain\Athlete\HeartRateZone;

enum HeartRateZoneMode: string
{
    case RELATIVE = 'relative';
    case ABSOLUTE = 'absolute';
}
