<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\MaxHeartRate;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class DateRangeBased implements MaxHeartRateFormula
{
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

    public function addRange(SerializableDateTime $on, int $maxHeartRate): self
    {
        $key = $on->getTimestamp();
        if (!empty($this->ranges[$key])) {
            throw new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA cannot contain the same date more than once');
        }

        $this->ranges[$key] = [$on, $maxHeartRate];

        return new self($this->ranges);
    }

    public function calculate(int $age, SerializableDateTime $on): int
    {
        $on = SerializableDateTime::fromString($on->format('Y-m-d'));
        foreach ($this->ranges as $range) {
            [$date, $maxHeartRate] = $range;
            if ($on->isAfterOrOn($date)) {
                return $maxHeartRate;
            }
        }

        throw new InvalidMaxHeartRateFormula(sprintf('MAX_HEART_RATE_FORMULA: could not determine max heart rate for given date "%s"', $on->format('Y-m-d')));
    }
}
