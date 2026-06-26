<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<GearMaintenanceLog>
 */
final class GearMaintenanceLogs extends Collection
{
    public function getItemClassName(): string
    {
        return GearMaintenanceLog::class;
    }

    public function filterOnGear(GearId $gearId): self
    {
        return $this->filter(fn (GearMaintenanceLog $log): bool => $log->getGearId() == $gearId);
    }

    public function filterOnMaintenanceTask(MaintenanceTaskId $maintenanceTaskId): self
    {
        return $this->filter(fn (GearMaintenanceLog $log): bool => $log->getMaintenanceTaskId() == $maintenanceTaskId);
    }

    public function sortOnDateDesc(): self
    {
        return $this->usort(
            fn (GearMaintenanceLog $a, GearMaintenanceLog $b): int => $b->getPerformedOn()->getTimestamp() <=> $a->getPerformedOn()->getTimestamp()
        );
    }

    public function getMostRecent(): ?GearMaintenanceLog
    {
        return $this->sortOnDateDesc()->getFirst();
    }
}
