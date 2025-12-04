<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

enum TrainingGoalType: string
{
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
    case MOVING_TIME = 'movingTime';

    /**
     * @return TrainingGoalType[]
     */
    public static function lengthRelated(): array
    {
        return [self::DISTANCE, self::ELEVATION];
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            self::MOVING_TIME => 'time',
            self::DISTANCE => 'distance',
            self::ELEVATION => 'elevation',
        };
    }
}
