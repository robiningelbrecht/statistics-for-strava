<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindCarbonSaved;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Year;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindCarbonSaved\FindCarbonSavedResponse>
 */
final readonly class FindCarbonSaved implements Query
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
