<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task;

use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;

final class MaintenanceTask
{
    private ?MaintenanceTaskTag $mostRecentMaintenanceTaskTag = null;

    private function __construct(
        private readonly Tag $tag,
        private readonly Name $label,
        private readonly int $intervalValue,
        private readonly IntervalUnit $intervalUnit,
    ) {
    }

    public static function create(
        Tag $tag,
        Name $label,
        int $intervalValue,
        IntervalUnit $intervalUnit,
    ): self {
        return new self(
            tag: $tag,
            label: $label,
            intervalValue: $intervalValue,
            intervalUnit: $intervalUnit,
        );
    }

    public function getTag(): Tag
    {
        return $this->tag;
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

    public function withMostRecentMaintenanceTaskTag(?MaintenanceTaskTag $maintenanceTaskTag): self
    {
        return clone ($this, [
            'mostRecentMaintenanceTaskTag' => $maintenanceTaskTag,
        ]);
    }

    public function getMostRecentMaintenanceTaskTag(): ?MaintenanceTaskTag
    {
        return $this->mostRecentMaintenanceTaskTag;
    }
}
