<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

enum TrainingGoalType: string
{
    case DISTANCE = 'distance';
    case ELEVATION = 'elevation';
    case MOVING_TIME = 'movingTime';
    case NUMBER_OF_ACTIVITIES = 'numberOfActivities';
    case CALORIES = 'calories';

    /**
     * @return TrainingGoalType[]
     */
    public static function lengthRelated(): array
    {
        return [self::DISTANCE, self::ELEVATION];
    }

    /**
     * @return TrainingGoalType[]
     */
    public static function simpleUnitRelated(): array
    {
        return [self::NUMBER_OF_ACTIVITIES, self::CALORIES];
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            self::MOVING_TIME => 'time',
            self::DISTANCE => 'distance',
            self::ELEVATION => 'elevation',
            self::CALORIES => 'calories',
            self::NUMBER_OF_ACTIVITIES => 'hashtag',
        };
    }
}
