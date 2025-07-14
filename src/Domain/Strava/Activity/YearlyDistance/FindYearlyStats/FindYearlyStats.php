<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance\FindYearlyStats;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Strava\Activity\YearlyDistance\FindYearlyStats\FindYearlyStatsResponse>
 */
final readonly class FindYearlyStats implements Query
{
}
