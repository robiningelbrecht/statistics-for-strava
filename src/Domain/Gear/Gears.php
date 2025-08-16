<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Gear>
 */
final class Gears extends Collection
{
    public function getItemClassName(): string
    {
        return Gear::class;
    }

    public function sortByIsRetired(): self
    {
        return $this->usort(fn (Gear $a, Gear $b) => $a->isRetired() <=> $b->isRetired());
    }

    public function getByGearId(GearId $gearId): ?Gear
    {
        $gears = $this->filter(fn (Gear $gear) => $gearId == $gear->getId())->toArray();

        return reset($gears) ?: null;
    }
}
