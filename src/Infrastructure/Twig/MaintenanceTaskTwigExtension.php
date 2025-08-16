<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Activity\ActivityId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgress;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Domain\Gear\Maintenance\Task\Progress\ProgressCalculationContext;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Attribute\AsTwigFunction;

final readonly class MaintenanceTaskTwigExtension
{
    public function __construct(
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
    ) {
    }

    #[AsTwigFunction('calculateMaintenanceTaskProgress')]
    public function calculateProgress(
        GearIds $gearIds,
        ?ActivityId $lastTaggedOnActivityId,
        ?SerializableDateTime $lastTaggedOn,
        IntervalUnit $intervalUnit,
        int $intervalValue,
    ): MaintenanceTaskProgress {
        if (null === $lastTaggedOnActivityId || null === $lastTaggedOn) {
            return MaintenanceTaskProgress::from(0, '0');
        }

        $context = ProgressCalculationContext::from(
            gearIds: $gearIds,
            lastTaggedOnActivityId: $lastTaggedOnActivityId,
            lastTaggedOn: $lastTaggedOn,
            intervalUnit: $intervalUnit,
            intervalValue: $intervalValue,
        );

        return $this->maintenanceTaskProgressCalculator->calculateProgressFor($context);
    }
}
