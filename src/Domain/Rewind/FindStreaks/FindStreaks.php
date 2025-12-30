<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindStreaks;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Rewind\FindStreaks\FindStreaksResponse>
 */
final readonly class FindStreaks implements Query
{
    public function __construct(
        private Years $years,
        private ?SportTypes $restrictToSportTypes,
    ) {
    }

    public function getYears(): Years
    {
        return $this->years;
    }

    public function getRestrictToSportTypes(): ?SportTypes
    {
        return $this->restrictToSportTypes;
    }
}
