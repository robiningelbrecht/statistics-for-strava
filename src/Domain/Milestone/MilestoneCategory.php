<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

enum MilestoneCategory: string
{
    case FIRST = 'first';
    case CUMULATIVE_DISTANCE = 'cumulativeDistance';
    case ACTIVITY_DISTANCE = 'activityDistance';
    case CUMULATIVE_ELEVATION = 'cumulativeElevation';
    case ACTIVITY_ELEVATION = 'activityElevation';
    case CUMULATIVE_MOVING_TIME = 'cumulativeMovingTime';
    case ACTIVITY_MOVING_TIME = 'activityMovingTime';
    case ACTIVITY_COUNT = 'activityCount';
    case PERSONAL_BEST = 'personalBest';
    case EDDINGTON = 'eddington';
    case STREAK = 'streak';

    public function getFilterGroup(): MilestoneFilterGroup
    {
        return match ($this) {
            self::FIRST => MilestoneFilterGroup::FIRST,
            self::CUMULATIVE_DISTANCE, self::ACTIVITY_DISTANCE => MilestoneFilterGroup::DISTANCE,
            self::CUMULATIVE_ELEVATION, self::ACTIVITY_ELEVATION => MilestoneFilterGroup::ELEVATION,
            self::CUMULATIVE_MOVING_TIME, self::ACTIVITY_MOVING_TIME => MilestoneFilterGroup::MOVING_TIME,
            self::ACTIVITY_COUNT => MilestoneFilterGroup::ACTIVITY,
            self::PERSONAL_BEST => MilestoneFilterGroup::PERSONAL_BEST,
            self::EDDINGTON => MilestoneFilterGroup::EDDINGTON,
            self::STREAK => MilestoneFilterGroup::STREAK,
        };
    }
}
