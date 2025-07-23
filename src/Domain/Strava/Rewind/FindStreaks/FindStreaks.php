<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindStreaks;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Strava\Rewind\FindStreaks\FindStreaksResponse>
 */
final readonly class FindStreaks implements Query
{
    public function __construct(
        private Years $years,
    ) {
    }

    public function getYears(): Years
    {
        return $this->years;
    }
}
