<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindTotalsPerMonth;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @implements Query<\App\Domain\Rewind\FindTotalsPerMonth\FindTotalsPerMonthResponse>
 */
final readonly class FindTotalsPerMonth implements Query
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
