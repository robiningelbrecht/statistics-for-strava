<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearIds;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<\App\Domain\Gear\Maintenance\GearComponent>
 */
final class GearComponents extends Collection
{
    public function getItemClassName(): string
    {
        return GearComponent::class;
    }

    public function getAllReferencedGearIds(): GearIds
    {
        $gearIds = GearIds::empty();
        foreach ($this as $gearComponent) {
            foreach ($gearComponent->getAttachedTo() as $gearId) {
                $gearIds->add($gearId);
            }
        }

        return $gearIds->unique();
    }
}
