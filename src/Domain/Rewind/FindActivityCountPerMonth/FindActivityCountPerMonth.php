<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindActivityCountPerMonth;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Rewind\FindActivityCountPerMonth\FindActivityCountPerMonthResponse>
 */
final readonly class FindActivityCountPerMonth implements Query
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
