<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;

final readonly class ActivityBasedMaintenanceTaskTagRepository implements MaintenanceTaskTagRepository
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private GearMaintenanceConfig $gearMaintenanceConfig,
    ) {
    }

    public function findAll(): MaintenanceTaskTags
    {
        $activities = $this->activityRepository->findAll();
        $tasks = MaintenanceTaskTags::empty();

        /** @var \App\Domain\Gear\Maintenance\GearComponent $gearComponent */
        foreach ($this->gearMaintenanceConfig->getGearComponents() as $gearComponent) {
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
