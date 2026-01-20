<?php

declare(strict_types=1);

namespace App\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\ProvideDateRangeBasedFormula;

final class DateRangeBased implements MaxHeartRateFormula
{
    use ProvideDateRangeBasedFormula;

    private function __construct(
        /** @var array<int, array<int, mixed>> */
        private array $ranges,
    ) {
        krsort($this->ranges);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function getRanges(): array
    {
        return $this->ranges;
    }
}
