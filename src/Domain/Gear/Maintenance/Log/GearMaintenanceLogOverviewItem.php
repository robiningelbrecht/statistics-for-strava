<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Log;

use App\Infrastructure\Repository\Item;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class GearMaintenanceLogOverviewItem implements Item
{
    private function __construct(
        private GearMaintenanceLogId $gearMaintenanceLogId,
        private string $gearName,
        private string $componentLabel,
        private string $taskLabel,
        private SerializableDateTime $performedOn,
    ) {
    }

    public static function fromState(
        GearMaintenanceLogId $gearMaintenanceLogId,
        string $gearName,
        string $componentLabel,
        string $taskLabel,
        SerializableDateTime $performedOn,
    ): self {
        return new self(
            gearMaintenanceLogId: $gearMaintenanceLogId,
            gearName: $gearName,
            componentLabel: $componentLabel,
            taskLabel: $taskLabel,
            performedOn: $performedOn,
        );
    }

    public function getGearMaintenanceLogId(): GearMaintenanceLogId
    {
        return $this->gearMaintenanceLogId;
    }

    public function getGearName(): string
    {
        return $this->gearName;
    }

    public function getComponentLabel(): string
    {
        return $this->componentLabel;
    }

    public function getTaskLabel(): string
    {
        return $this->taskLabel;
    }

    public function getPerformedOn(): SerializableDateTime
    {
        return $this->performedOn;
    }
}
