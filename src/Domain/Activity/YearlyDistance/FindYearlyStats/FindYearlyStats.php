<?php

declare(strict_types=1);

namespace App\Domain\Activity\YearlyDistance\FindYearlyStats;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Activity\YearlyDistance\FindYearlyStats\FindYearlyStatsResponse>
 */
final readonly class FindYearlyStats implements Query
{
}
