<?php

declare(strict_types=1);

namespace App\Domain\Activity\Training\FindNumberOfRestDays;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\DateRange;

/**
 * @implements Query<\App\Domain\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDaysResponse>
 */
final readonly class FindNumberOfRestDays implements Query
{
    public function __construct(
        private DateRange $dateRange,
    ) {
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }
}
