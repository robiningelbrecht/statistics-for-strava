<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTasks;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTag;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTags;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;

final class GearComponent
{
    private MaintenanceTasks $maintenanceTasks;

    private function __construct(
        private readonly Tag $tag,
        private readonly Name $label,
        private readonly GearIds $attachedTo,
        private readonly ?string $imgSrc,
    ) {
        $this->maintenanceTasks = MaintenanceTasks::empty();
    }

    public static function create(
        Tag $tag,
        Name $label,
        GearIds $attachedTo,
        ?string $imgSrc,
    ): self {
        return new self(
            tag: $tag,
            label: $label,
            attachedTo: $attachedTo,
            imgSrc: $imgSrc,
        );
    }

    public function addMaintenanceTask(MaintenanceTask $task): void
    {
        $this->maintenanceTasks->add($task);
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function getLabel(): Name
    {
        return $this->label;
    }

    public function getAttachedTo(): GearIds
    {
        return $this->attachedTo;
    }

    public function isAttachedTo(GearId $gearId): bool
    {
        return $this->getAttachedTo()->has($gearId);
    }

    public function getImgSrc(): ?string
    {
        return $this->imgSrc;
    }

    public function getMaintenanceTasks(): MaintenanceTasks
    {
        return $this->maintenanceTasks;
    }

    public function enrichWithMaintenanceTaskTags(MaintenanceTaskTags $maintenanceTaskTags): void
    {
        /** @var MaintenanceTask $maintenanceTask */
        foreach ($this->maintenanceTasks as $maintenanceTask) {
            $mostRecentMaintenance = null;
            /* @var MaintenanceTaskTag $maintenanceTaskTag */
            foreach ($maintenanceTaskTags as $maintenanceTaskTag) {
                if ($maintenanceTask->getTag() != $maintenanceTaskTag->getTag()) {
                    continue;
                }

                if ($mostRecentMaintenance
                    && $maintenanceTaskTag->getTaggedOn()->isBeforeOrOn($mostRecentMaintenance->getTaggedOn())
                ) {
                    continue;
                }
                $mostRecentMaintenance = $maintenanceTaskTag;
            }

            $maintenanceTask->enrichWithMostRecentMaintenanceTaskTag($mostRecentMaintenance);
        }
    }

    public function normalizeGearIds(GearIds $normalizedGearIds): void
    {
        /** @var GearId $gearId */
        foreach ($this->getAttachedTo() as $gearId) {
            if ($gearId->isPrefixedWithStravaPrefix()) {
                continue;
            }

            foreach ($normalizedGearIds as $normalizedGearId) {
                // Try to match the gear id with the prefix.
                if (!$gearId->matches($normalizedGearId)) {
                    continue;
                }

                // If we found a match, we can replace it.
                $this->attachedTo->replace($gearId, $normalizedGearId);
            }
        }
    }
}
