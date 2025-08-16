<?php

declare(strict_types=1);

namespace App\Domain\Activity\Lap;

use App\Domain\Activity\ActivityId;

interface ActivityLapRepository
{
    public function findBy(ActivityId $activityId): ActivityLaps;

    public function add(ActivityLap $lap): void;

    public function isImportedForActivity(ActivityId $activityId): bool;

    public function deleteForActivity(ActivityId $activityId): void;
}
