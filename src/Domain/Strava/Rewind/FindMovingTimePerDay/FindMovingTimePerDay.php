<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindMovingTimePerDay;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Year;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindMovingTimePerDay\FindMovingTimePerDayResponse>
 */
final readonly class FindMovingTimePerDay implements Query
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
