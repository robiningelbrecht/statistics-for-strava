<?php

declare(strict_types=1);

namespace App\Domain\Gear\FindMovingTimePerGear;

use App\Domain\Activity\ActivityTypes;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGearResponse>
 */
final readonly class FindMovingTimePerGear implements Query
{
    public function __construct(
        private Years $years,
        private ?ActivityTypes $activityTypes,
    ) {
    }

    public function getYears(): Years
    {
        return $this->years;
    }

    public function getActivityTypes(): ?ActivityTypes
    {
        return $this->activityTypes;
    }
}
