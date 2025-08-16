<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
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
        $allGearComponents = $this->gearMaintenanceConfig->getEnrichedGearComponents($maintenanceTaskTags);

        /** @var \App\Domain\Gear\Maintenance\GearComponent $gearComponent */
        foreach ($allGearComponents as $gearComponent) {
            $gearComponent->enrichWithMaintenanceTaskTags($maintenanceTaskTags);
            /** @var \App\Domain\Gear\Maintenance\Task\MaintenanceTask $maintenanceTask */
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
