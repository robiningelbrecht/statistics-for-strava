<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Domain\Gear\Maintenance\Log\GearMaintenanceLog;
use App\Infrastructure\ValueObject\String\Name;

final class MaintenanceTask
{
    private ?GearMaintenanceLog $mostRecentMaintenance = null;

    private function __construct(
        private readonly MaintenanceTaskId $id,
        private readonly Name $label,
        private readonly int $intervalValue,
        private readonly IntervalUnit $intervalUnit,
    ) {
    }

    public static function create(
        MaintenanceTaskId $id,
        Name $label,
        int $intervalValue,
        IntervalUnit $intervalUnit,
    ): self {
        return new self(
            id: $id,
            label: $label,
            intervalValue: $intervalValue,
            intervalUnit: $intervalUnit,
        );
    }

    public function getId(): MaintenanceTaskId
    {
        return $this->id;
    }

    public function getLabel(): Name
    {
        return $this->label;
    }

    public function getIntervalValue(): int
    {
        return $this->intervalValue;
    }

    public function getIntervalUnit(): IntervalUnit
    {
        return $this->intervalUnit;
    }

    public function withMostRecentMaintenance(?GearMaintenanceLog $mostRecentMaintenance): self
    {
        return clone ($this, [
            'mostRecentMaintenance' => $mostRecentMaintenance,
        ]);
    }

    public function getMostRecentMaintenance(): ?GearMaintenanceLog
    {
        return $this->mostRecentMaintenance;
    }
}
