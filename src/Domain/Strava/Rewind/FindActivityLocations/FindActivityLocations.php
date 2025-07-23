<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindActivityLocations;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindActivityLocations\FindActivityLocationsResponse>
 */
final readonly class FindActivityLocations implements Query
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
