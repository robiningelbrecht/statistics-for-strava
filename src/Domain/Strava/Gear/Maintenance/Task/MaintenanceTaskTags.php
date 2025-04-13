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

        /* @var MaintenanceTaskTag $task */
        foreach ($this as $maintenanceTask) {
            if ($maintenanceTask->getTag() != $taskMaintenanceTag) {
                continue;
            }
            if ($mostRecentTask
                && $maintenanceTask->getTaggedOn()->isBeforeOrOn($mostRecentTask->getTaggedOn())
            ) {
                continue;
            }
            $mostRecentTask = $maintenanceTask;
        }

        return $mostRecentTask;
    }

    public function filterOnInvalid(): self
    {
        return $this->filter(fn (MaintenanceTaskTag $tag) => !$tag->isValid());
    }
}
