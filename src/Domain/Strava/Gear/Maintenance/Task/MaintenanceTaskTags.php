<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\String\Tag;

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

    public function getMostRecentFor(Tag $taskMaintenanceTag): ?MaintenanceTaskTag
    {
        $mostRecentTask = null;

        /* @var MaintenanceTaskTag $maintenanceTaskTag */
        foreach ($this as $maintenanceTaskTag) {
            if ($maintenanceTaskTag->getTag() != $taskMaintenanceTag) {
                continue;
            }

            if ($mostRecentTask
                && $maintenanceTaskTag->getTaggedOn()->isBeforeOrOn($mostRecentTask->getTaggedOn())
            ) {
                continue;
            }
            $mostRecentTask = $maintenanceTaskTag;
        }

        return $mostRecentTask;
    }

    public function filterOnInvalid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag) => !$tag->isValid());
    }
}
