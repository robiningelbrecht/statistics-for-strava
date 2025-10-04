<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityType;

interface ActivityBestEffortRepository
{
    public function add(ActivityBestEffort $activityBestEffort): void;

    public function findAll(): ActivityBestEfforts;

    public function hasData(): bool;

    public function findBestEffortsFor(ActivityType $activityType): ActivityBestEfforts;

    public function findBestEffortHistory(ActivityType $activityType): ActivityBestEfforts;

    public function findActivityIdsThatNeedBestEffortsCalculation(): ActivityIds;

    public function deleteForActivity(ActivityId $activityId): void;
}
