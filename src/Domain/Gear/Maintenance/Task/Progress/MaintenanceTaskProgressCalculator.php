<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Gear\GearIds;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Infrastructure\Config\AppConfig;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MaintenanceTaskProgressCalculator
{
    /**
     * @param iterable<MaintenanceTaskProgressCalculation> $maintenanceTaskProgressCalculations
     */
    public function __construct(
        #[AutowireIterator('app.maintenance_progress_calculation')]
        private iterable $maintenanceTaskProgressCalculations,
        private AppConfig $config,
        private MaintenanceTaskTagRepository $maintenanceTaskTagRepository,
        private GearRepository $gearRepository,
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
        $gearMaintenanceConfig = $this->config->loadGearMaintenance();
        if (!$gearMaintenanceConfig->isFeatureEnabled()) {
            return $gearIdsThatHaveDueTasks;
        }

        $allGears = $this->gearRepository->findAll();
        $maintenanceTaskTags = $this->maintenanceTaskTagRepository->findAll()->filterOnValid();
        $allGearComponents = $gearMaintenanceConfig->getEnrichedGearComponents($maintenanceTaskTags);

        foreach ($allGearComponents as $gearComponent) {
            $gearComponent = $gearComponent->withMaintenanceTaskTags($maintenanceTaskTags);
            foreach ($gearComponent->getMaintenanceTasks() as $maintenanceTask) {
                if (!($mostRecentTag = $maintenanceTask->getMostRecentMaintenanceTaskTag()) instanceof \App\Domain\Gear\Maintenance\Task\MaintenanceTaskTag) {
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
                        if ($gearMaintenanceConfig->ignoreRetiredGear() && $allGears->getByGearId($gearId)?->isRetired()) {
                            continue;
                        }
                        $gearIdsThatHaveDueTasks->add($gearId);
                    }
                }
            }
        }

        return $gearIdsThatHaveDueTasks;
    }
}
