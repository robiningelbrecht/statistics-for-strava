<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\Maintenance\Tag;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class MaintenanceTaskTag
{
    private function __construct(
        private Tag $maintenanceTaskTag,
        private ActivityId $taggedOnActivityId,
        private ?GearId $taggedForGearId,
        private SerializableDateTime $taggedOn,
        private string $activityName,
        private bool $isValid,
    ) {
    }

    public static function for(
        Tag $maintenanceTaskTag,
        ActivityId $taggedOnActivityId,
        ?GearId $taggedForGearId,
        SerializableDateTime $taggedOn,
        string $activityName,
        bool $isValid,
    ): self {
        return new self(
            maintenanceTaskTag: $maintenanceTaskTag,
            taggedOnActivityId: $taggedOnActivityId,
            taggedForGearId: $taggedForGearId,
            taggedOn: $taggedOn,
            activityName: $activityName,
            isValid: $isValid,
        );
    }

    public function getTag(): Tag
    {
        return $this->maintenanceTaskTag;
    }

    public function getTaggedOnActivityId(): ActivityId
    {
        return $this->taggedOnActivityId;
    }

    public function getTaggedForGearId(): ?GearId
    {
        return $this->taggedForGearId;
    }

    public function getTaggedOn(): SerializableDateTime
    {
        return $this->taggedOn;
    }

    public function getActivityName(): string
    {
        return $this->activityName;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }
}
