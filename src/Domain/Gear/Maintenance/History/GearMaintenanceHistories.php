<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\History;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<GearMaintenanceHistory>
 */
final class GearMaintenanceHistories extends Collection
{
    public function getItemClassName(): string
    {
        return GearMaintenanceHistory::class;
    }

    public function filterOnGear(GearId $gearId): self
    {
        return $this->filter(fn (GearMaintenanceHistory $history): bool => $history->getGearId() == $gearId);
    }

    public function filterOnMaintenanceTask(MaintenanceTaskId $maintenanceTaskId): self
    {
        return $this->filter(fn (GearMaintenanceHistory $history): bool => $history->getMaintenanceTaskId() == $maintenanceTaskId);
    }

    public function sortOnDateDesc(): self
    {
        return $this->usort(
            fn (GearMaintenanceHistory $a, GearMaintenanceHistory $b): int => $b->getPerformedOn()->getTimestamp() <=> $a->getPerformedOn()->getTimestamp()
        );
    }

    public function getMostRecent(): ?GearMaintenanceHistory
    {
        return $this->sortOnDateDesc()->getFirst();
    }
}
