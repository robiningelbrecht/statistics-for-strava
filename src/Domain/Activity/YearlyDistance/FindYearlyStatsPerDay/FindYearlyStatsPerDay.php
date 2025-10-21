<?php

declare(strict_types=1);

namespace App\Domain\Activity\YearlyDistance\FindYearlyStatsPerDay;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<FindYearlyStatsPerDayResponse>
 */
final readonly class FindYearlyStatsPerDay implements Query
{
}
