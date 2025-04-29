<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindDistancePerMonth;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Year;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindDistancePerMonth\FindDistancePerMonthResponse>
 */
final readonly class FindDistancePerMonth implements Query
{
    public function __construct(
        private Year $year,
    ) {
    }

    public function getYear(): Year
    {
        return $this->year;
    }
}
