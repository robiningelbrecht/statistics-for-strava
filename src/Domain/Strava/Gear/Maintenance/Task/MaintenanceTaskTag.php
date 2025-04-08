<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Gear\Maintenance\Tag;

final readonly class MaintenanceTaskTag
{
    private function __construct(
        private Tag $maintenanceTaskTag,
        private ActivityId $activityId,
    ) {
    }

    public static function for(
        Tag $maintenanceTaskTag,
        ActivityId $activityId,
    ): self {
        return new self(
            maintenanceTaskTag: $maintenanceTaskTag,
            activityId: $activityId
        );
    }

    public function getMaintenanceTaskTag(): Tag
    {
        return $this->maintenanceTaskTag;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }
}
