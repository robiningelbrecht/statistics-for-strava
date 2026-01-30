<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Gear\Maintenance\Task\MaintenanceTasks;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTags;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;
use Money\Money;

final readonly class GearComponent
{
    private MaintenanceTasks $maintenanceTasks;

    private function __construct(
        private Tag $tag,
        private Name $label,
        private GearIds $attachedTo,
        private ?string $imgSrc,
        private ?Money $purchasePrice,
    ) {
        $this->maintenanceTasks = MaintenanceTasks::empty();
    }

    public static function create(
        Tag $tag,
        Name $label,
        GearIds $attachedTo,
        ?string $imgSrc,
        ?Money $purchasePrice,
    ): self {
        return new self(
            tag: $tag,
            label: $label,
            attachedTo: $attachedTo,
            imgSrc: $imgSrc,
            purchasePrice: $purchasePrice,
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

    public function getPurchasePrice(): ?Money
    {
        return $this->purchasePrice;
    }

    public function getMaintenanceTasks(): MaintenanceTasks
    {
        return $this->maintenanceTasks;
    }

    public function withMaintenanceTaskTags(MaintenanceTaskTags $maintenanceTaskTags): self
    {
        $maintenanceTasks = MaintenanceTasks::empty();
        foreach ($this->maintenanceTasks as $maintenanceTask) {
            $mostRecentMaintenance = null;
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
            $maintenanceTasks->add($maintenanceTask->withMostRecentMaintenanceTaskTag($mostRecentMaintenance));
        }

        return clone ($this, [
            'maintenanceTasks' => $maintenanceTasks,
        ]);
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
