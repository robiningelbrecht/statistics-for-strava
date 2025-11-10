<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<CustomGear>
 */
final class CustomGears extends Collection
{
    public function getItemClassName(): string
    {
        return Gear::class;
    }

    public function getGearIds(): GearIds
    {
        return GearIds::fromArray(
            $this->map(static fn (CustomGear $gear): GearId => $gear->getId())
        );
    }

    /**
     * @return string[]
     */
    public function getAllGearTags(): array
    {
        return $this->map(static fn (CustomGear $gear): string => $gear->getTag());
    }
}
