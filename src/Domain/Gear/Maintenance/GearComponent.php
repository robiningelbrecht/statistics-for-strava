<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\History\GearMaintenanceHistories;
use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Domain\Gear\Maintenance\Task\MaintenanceTasks;
use App\Infrastructure\ValueObject\String\Name;
use Money\Money;

final readonly class GearComponent
{
    private MaintenanceTasks $maintenanceTasks;

    private function __construct(
        private Name $label,
        private GearIds $attachedTo,
        private ?string $imgSrc,
        private ?Money $purchasePrice,
    ) {
        $this->maintenanceTasks = MaintenanceTasks::empty();
    }

    public static function create(
        Name $label,
        GearIds $attachedTo,
        ?string $imgSrc,
        ?Money $purchasePrice,
    ): self {
        return new self(
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

    public function withMaintenanceHistory(GearMaintenanceHistories $maintenanceHistories): self
    {
        $maintenanceTasks = MaintenanceTasks::empty();
        foreach ($this->maintenanceTasks as $maintenanceTask) {
            $mostRecentMaintenance = $maintenanceHistories
                ->filterOnMaintenanceTask($maintenanceTask->getId())
                ->getMostRecent();

            $maintenanceTasks->add($maintenanceTask->withMostRecentMaintenance($mostRecentMaintenance));
        }

        return clone ($this, [
            'maintenanceTasks' => $maintenanceTasks,
        ]);
    }

    public function normalizeGearIds(GearIds $normalizedGearIds): void
    {
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
