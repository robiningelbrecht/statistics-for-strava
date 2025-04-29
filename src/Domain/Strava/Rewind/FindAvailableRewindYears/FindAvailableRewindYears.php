<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindAvailableRewindYears;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYearsResponse>
 */
final readonly class FindAvailableRewindYears implements Query
{
    public function __construct(
        private SerializableDateTime $now,
    ) {
    }

    public function getNow(): SerializableDateTime
    {
        return $this->now;
    }
}
