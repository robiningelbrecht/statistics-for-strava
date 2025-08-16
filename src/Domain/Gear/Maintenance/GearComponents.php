<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearIds;
use App\Infrastructure\ValueObject\Collection;

final class GearComponents extends Collection
{
    public function getItemClassName(): string
    {
        return GearComponent::class;
    }

    /**
     * @return string[]
     */
    public function getAllMaintenanceTags(): array
    {
        $tags = [];
        /** @var GearComponent $gearComponent */
        foreach ($this as $gearComponent) {
            foreach ($gearComponent->getMaintenanceTasks() as $maintenanceTask) {
                $tags[] = (string) $maintenanceTask->getTag();
            }
        }

        return $tags;
    }

    public function getAllReferencedGearIds(): GearIds
    {
        $gearIds = GearIds::empty();
        /** @var GearComponent $gearComponent */
        foreach ($this as $gearComponent) {
            foreach ($gearComponent->getAttachedTo() as $gearId) {
                $gearIds->add($gearId);
            }
        }

        return $gearIds;
    }

    /**
     * @return string[]
     */
    public function getAllReferencedImages(): array
    {
        $images = [];
        /** @var GearComponent $gearComponent */
        foreach ($this as $gearComponent) {
            $images[] = $gearComponent->getImgSrc();
        }

        return array_filter($images);
    }
}
