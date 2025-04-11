<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\ValueObject\Collection;

final class GearComponents extends Collection
{
    public function getItemClassName(): string
    {
        return GearComponent::class;
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
