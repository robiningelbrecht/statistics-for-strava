<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

enum GearType: string
{
    case IMPORTED = 'imported';
    case CUSTOM = 'custom';
}
