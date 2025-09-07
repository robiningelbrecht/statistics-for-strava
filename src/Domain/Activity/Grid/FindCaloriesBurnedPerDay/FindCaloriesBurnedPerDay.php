<?php

declare(strict_types=1);

namespace App\Domain\Activity\Grid\FindCaloriesBurnedPerDay;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Activity\Grid\FindCaloriesBurnedPerDay\FindCaloriesBurnedPerDayResponse>
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
