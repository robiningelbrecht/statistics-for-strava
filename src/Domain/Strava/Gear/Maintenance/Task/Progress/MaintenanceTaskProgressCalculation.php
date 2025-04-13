<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Gear\Maintenance\IntervalUnit;

interface MaintenanceTaskProgressCalculation
{
    public function supports(IntervalUnit $intervalUnit): bool;

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress;
}
