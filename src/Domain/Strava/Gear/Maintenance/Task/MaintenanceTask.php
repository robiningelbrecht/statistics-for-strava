<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task;

use App\Domain\Strava\Gear\Maintenance\Tag;
use App\Infrastructure\ValueObject\String\Name;

final readonly class MaintenanceTask
{
    private function __construct(
        private Tag $tag,
        private Name $label,
        private int $intervalValue,
        private IntervalUnit $intervalUnit,
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
}
