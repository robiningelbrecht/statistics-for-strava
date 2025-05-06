<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTasks;
use App\Infrastructure\ValueObject\String\Name;

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
