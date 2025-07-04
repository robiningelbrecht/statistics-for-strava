<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency\FindConsistencyMetricsPerMonth;

use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Strava\Challenge\Consistency\FindConsistencyMetricsPerMonth\FindConsistencyMetricsPerMonthResponse>
 */
final readonly class FindConsistencyMetricsPerMonth implements Query
{
    public function __construct(
        private SportTypes $sportTypes,
    ) {
    }

    public function getSportTypes(): SportTypes
    {
        return $this->sportTypes;
    }
}
