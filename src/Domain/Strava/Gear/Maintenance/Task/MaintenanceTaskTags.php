<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Gear\Maintenance\Tag;
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
