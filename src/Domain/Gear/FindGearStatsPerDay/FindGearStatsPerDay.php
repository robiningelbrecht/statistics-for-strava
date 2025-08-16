<?php

declare(strict_types=1);

namespace App\Domain\Gear\FindGearStatsPerDay;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Gear\FindGearStatsPerDay\FindGearStatsPerDayResponse>
 */
final readonly class FindGearStatsPerDay implements Query
{
}
