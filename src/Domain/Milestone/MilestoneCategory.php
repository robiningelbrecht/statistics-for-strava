<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

enum MilestoneCategory: string
{
    case FIRST = 'first';
    case CUMULATIVE_DISTANCE = 'cumulativeDistance';
    case CUMULATIVE_ELEVATION = 'cumulativeElevation';
    case CUMULATIVE_MOVING_TIME = 'cumulativeMovingTime';
    case ACTIVITY_COUNT = 'activityCount';
    case PERSONAL_BEST = 'personalBest';
    case EDDINGTON = 'eddington';
    case STREAK = 'streak';
}
