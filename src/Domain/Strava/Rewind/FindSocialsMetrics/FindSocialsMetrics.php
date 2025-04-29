<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindSocialsMetrics;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Year;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindSocialsMetrics\FindSocialsMetricsResponse>
 */
final readonly class FindSocialsMetrics implements Query
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
