<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\ActivityGrid\FindCaloriesBurnedPerDay;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Dashboard\Widget\ActivityGrid\FindCaloriesBurnedPerDay\FindCaloriesBurnedPerDayResponse>
 */
final readonly class FindCaloriesBurnedPerDay implements Query
{
    public function __construct(
        private Years $years,
    ) {
    }

    public function getYears(): Years
    {
        return $this->years;
    }
}
