<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Domain\Gear\GearId;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<MaintenanceTaskTag>
 */
final class MaintenanceTaskTags extends Collection
{
    public function getItemClassName(): string
    {
        return MaintenanceTaskTag::class;
    }

    public function filterOnValid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag): bool => $tag->isValid());
    }

    public function filterOnGear(GearId $gearId): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag): bool => $tag->getTaggedForGearId() == $gearId);
    }

    public function sortOnDateDesc(): self
    {
        return $this->usort(
            fn (MaintenanceTaskTag $a, MaintenanceTaskTag $b): int => $b->getTaggedOn()->getTimestamp() <=> $a->getTaggedOn()->getTimestamp()
        );
    }

    public function filterOnInvalid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag): bool => !$tag->isValid());
    }
}
