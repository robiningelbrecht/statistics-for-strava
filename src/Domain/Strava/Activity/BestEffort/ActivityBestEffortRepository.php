<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityType;

interface ActivityBestEffortRepository
{
    public function add(ActivityBestEffort $activityBestEffort): void;

    public function findBestEffortsFor(ActivityType $activityType): ActivityBestEfforts;

    public function findActivityIdsThatNeedBestEffortsCalculation(): ActivityIds;

    public function deleteForActivity(ActivityId $activityId): void;
}
