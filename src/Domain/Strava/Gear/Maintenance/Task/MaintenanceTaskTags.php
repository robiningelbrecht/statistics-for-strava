<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\ValueObject\Collection;

final class MaintenanceTaskTags extends Collection
{
    public function getItemClassName(): string
    {
        return MaintenanceTaskTag::class;
    }

    public function filterOnValid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag) => $tag->isValid());
    }

    public function filterOnGear(GearId $gearId): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag) => $tag->getTaggedForGearId() == $gearId);
    }

    public function sortOnDateDesc(): self
    {
        return $this->usort(
            fn (MaintenanceTaskTag $a, MaintenanceTaskTag $b) => $b->getTaggedOn()->getTimestamp() <=> $a->getTaggedOn()->getTimestamp()
        );
    }

    public function filterOnInvalid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag) => !$tag->isValid());
    }
}
