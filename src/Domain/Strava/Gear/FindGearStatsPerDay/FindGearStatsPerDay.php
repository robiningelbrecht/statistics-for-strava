<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\FindGearStatsPerDay;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Strava\Gear\FindGearStatsPerDay\FindGearStatsPerDayResponse>
 */
final readonly class FindGearStatsPerDay implements Query
{
}
