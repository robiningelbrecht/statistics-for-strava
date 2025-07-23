<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindPersonalRecordsPerMonth\FindPersonalRecordsPerMonthResponse>
 */
final readonly class FindPersonalRecordsPerMonth implements Query
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
