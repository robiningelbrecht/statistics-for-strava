<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Domain\Activity\EnrichedActivities;
use App\Infrastructure\Config\Config;

final readonly class ActivityBasedMaintenanceTaskTagRepository implements MaintenanceTaskTagRepository
{
    public function __construct(
        private EnrichedActivities $enrichedActivities,
        private Config $config,
    ) {
    }

    public function findAll(): MaintenanceTaskTags
    {
        $activities = $this->enrichedActivities->findAll();
        $tasks = MaintenanceTaskTags::empty();

        $gearMaintenanceConfig = $this->config->loadGearMaintenance();
        foreach ($gearMaintenanceConfig->getGearComponents() as $gearComponent) {
            foreach ($gearComponent->getMaintenanceTasks() as $task) {
                foreach ($activities as $activity) {
                    if (!str_contains($activity->getOriginalName(), (string) $task->getTag())) {
                        continue;
                    }

                    $tasks->add(MaintenanceTaskTag::for(
                        maintenanceTaskTag: $task->getTag(),
                        taggedOnActivityId: $activity->getId(),
                        taggedForGearId: $activity->getGearId(),
                        taggedForGearComponentName: $gearComponent->getLabel(),
                        taggedOn: $activity->getStartDate(),
                        activityName: $activity->getOriginalName(),
                        isValid: is_null($activity->getGearId()) || $gearComponent->isAttachedTo($activity->getGearId())
                    ));
                }
            }
        }

        return $tasks;
    }
}
