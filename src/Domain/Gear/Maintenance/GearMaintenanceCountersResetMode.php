<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

enum GearMaintenanceCountersResetMode: string
{
    case NEXT_ACTIVITY_ONWARDS = 'nextActivityOnwards';
    case CURRENT_ACTIVITY_ONWARDS = 'currentActivityOnwards';
}
