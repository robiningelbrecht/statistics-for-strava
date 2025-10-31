<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\WeeklyGoals;

enum WeeklyGoalType: string
{
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
    case MOVING_TIME = 'movingTime';

    /**
     * @return WeeklyGoalType[]
     */
    public static function lengthRelated(): array
    {
        return [self::DISTANCE, self::ELEVATION];
    }
}
