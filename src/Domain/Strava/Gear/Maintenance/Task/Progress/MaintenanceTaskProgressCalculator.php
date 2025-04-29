<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;

final readonly class MaintenanceTaskProgressCalculator
{
    /**
     * @param iterable<MaintenanceTaskProgressCalculation> $maintenanceTaskProgressCalculations
     */
    public function __construct(
        private iterable $maintenanceTaskProgressCalculations,
        private GearMaintenanceConfig $gearMaintenanceConfig,
        private MaintenanceTaskTagRepository $maintenanceTaskTagRepository,
    ) {
    }

    public function calculateProgressFor(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $intervalUnit = $context->getIntervalUnit();

        foreach ($this->maintenanceTaskProgressCalculations as $calculation) {
            if (!$calculation->supports($intervalUnit)) {
                continue;
            }

            return $calculation->calculate($context);
        }

        throw new \RuntimeException(sprintf('No progress calculation found for interval unit: %s', $intervalUnit->value));
    }

    public function calculateIfATaskIsDue(): bool
    {
        $maintenanceTaskTags = $this->maintenanceTaskTagRepository->findAll()->filterOnValid();

        /** @var \App\Domain\Strava\Gear\Maintenance\GearComponent $gearComponent */
        foreach ($this->gearMaintenanceConfig->getGearComponents() as $gearComponent) {
            /** @var \App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTask $maintenanceTask */
            foreach ($gearComponent->getMaintenanceTasks() as $maintenanceTask) {
                if (!$mostRecentTag = $maintenanceTaskTags->getMostRecentFor($maintenanceTask->getTag())) {
                    continue;
                }

                $maintenanceTaskProgress = $this->calculateProgressFor(
                    ProgressCalculationContext::from(
                        gearIds: $gearComponent->getAttachedTo(),
                        lastTaggedOnActivityId: $mostRecentTag->getTaggedOnActivityId(),
                        lastTaggedOn: $mostRecentTag->getTaggedOn(),
                        intervalUnit: $maintenanceTask->getIntervalUnit(),
                        intervalValue: $maintenanceTask->getIntervalValue(),
                    )
                );

                if ($maintenanceTaskProgress->isDue()) {
                    return true;
                }
            }
        }

        return false;
    }
}
