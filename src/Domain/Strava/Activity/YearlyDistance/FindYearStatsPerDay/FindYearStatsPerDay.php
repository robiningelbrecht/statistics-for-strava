<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance\FindYearStatsPerDay;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Strava\Activity\YearlyDistance\FindYearStatsPerDay\FindYearStatsPerDayResponse>
 */
final readonly class FindYearStatsPerDay implements Query
{
}
