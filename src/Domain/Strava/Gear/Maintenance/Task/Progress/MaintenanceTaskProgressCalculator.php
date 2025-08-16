<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MaintenanceTaskProgressCalculator
{
    /**
     * @param iterable<MaintenanceTaskProgressCalculation> $maintenanceTaskProgressCalculations
     */
    public function __construct(
        #[AutowireIterator('app.maintenance_progress_calculation')]
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

    public function getGearIdsThatHaveDueTasks(): GearIds
    {
        $gearIdsThatHaveDueTasks = GearIds::empty();
        $maintenanceTaskTags = $this->maintenanceTaskTagRepository->findAll()->filterOnValid();
        $enrichedGearComponents = $this->gearMaintenanceConfig->getGearComponents()->enrichWithMaintenanceTaskTags($maintenanceTaskTags);

        /** @var \App\Domain\Strava\Gear\Maintenance\GearComponent $gearComponent */
        foreach ($enrichedGearComponents as $gearComponent) {
            $gearComponent->enrichWithMaintenanceTaskTags($maintenanceTaskTags);
            /** @var \App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTask $maintenanceTask */
            foreach ($gearComponent->getMaintenanceTasks() as $maintenanceTask) {
                if (!$mostRecentTag = $maintenanceTask->getMostRecentMaintenanceTaskTag()) {
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
                    foreach ($gearComponent->getAttachedTo() as $gearId) {
                        $gearIdsThatHaveDueTasks->add($gearId);
                    }
                }
            }
        }

        return $gearIdsThatHaveDueTasks;
    }
}
