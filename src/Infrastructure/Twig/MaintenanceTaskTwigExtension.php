<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;

final readonly class MaintenanceTaskTwigExtensions
{
    public function __construct(
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
    )
    {

    }
    public function calculateProgress(
        int $currentValue,
        int $maxValue,
        string $unit = 'km'
    ): string {
        if ($maxValue === 0) {
            return '0%';
        }

        $percentage = (int) round(($currentValue / $maxValue) * 100);

        return sprintf('%d%% (%d %s)', $percentage, $currentValue, $unit);
    }
}