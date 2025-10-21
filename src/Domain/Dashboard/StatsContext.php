<?php

declare(strict_types=1);

namespace App\Domain\Dashboard;

enum StatsContext: string
{
    case MOVING_TIME = 'movingTime';
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
}
