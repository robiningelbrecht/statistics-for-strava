<?php

declare(strict_types=1);

namespace App\Domain\Challenge\Consistency\FindConsistencyMetricsPerMonth;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Challenge\Consistency\FindConsistencyMetricsPerMonth\FindConsistencyMetricsPerMonthResponse>
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
