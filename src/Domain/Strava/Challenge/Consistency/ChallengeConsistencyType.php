<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

enum ChallengeConsistencyType: string
{
    case DISTANCE = 'distance';
    case DISTANCE_IN_ONE_ACTIVITY = 'distanceInOneActivity';
    case ELEVATION = 'elevation';
    case ELEVATION_IN_ONE_ACTIVITY = 'elevationInOneActivity';
    case MOVING_TIME = 'movingTime';
    case NUMBER_OF_ACTIVITIES = 'numberOfActivities';
}
