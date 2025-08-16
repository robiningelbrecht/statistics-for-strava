<?php

declare(strict_types=1);

namespace App\Domain\Calendar\FindMonthlyStats;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Calendar\FindMonthlyStats\FindMonthlyStatsResponse>
 */
final readonly class FindMonthlyStats implements Query
{
}
