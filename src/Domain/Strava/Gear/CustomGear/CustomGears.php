<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\ValueObject\Collection;

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
