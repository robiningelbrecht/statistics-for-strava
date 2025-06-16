<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap;

use App\Domain\Strava\Activity\ActivityId;

interface ActivityLapRepository
{
    public function findBy(ActivityId $activityId): ActivityLaps;

    public function add(ActivityLap $lab): void;

    public function isImportedForActivity(ActivityId $activityId): bool;

    public function deleteForActivity(ActivityId $activityId): void;
}
