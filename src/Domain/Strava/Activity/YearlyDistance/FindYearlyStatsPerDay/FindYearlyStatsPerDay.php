<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance\FindYearlyStatsPerDay;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Strava\Activity\YearlyDistance\FindYearlyStatsPerDay\FindYearlyStatsPerDayResponse>
 */
final readonly class FindYearlyStatsPerDay implements Query
{
}
