<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Time;

use App\Domain\Strava\Calendar\Months;

final readonly class Year implements \Stringable
{
    private function __construct(
        private int $year,
    ) {
    }

    public static function fromDate(SerializableDateTime $date): self
    {
        return new self(
            year: (int) $date->format('Y'),
        );
    }

    public static function fromInt(int $year): self
    {
        return new self(
            year: $year,
        );
    }

    public function __toString(): string
    {
        return (string) $this->year;
    }

    public function toInt(): int
    {
        return $this->year;
    }

    public function getRange(): DateRange
    {
        return DateRange::fromDates(
            from: SerializableDateTime::fromString(sprintf('%d-01-01', $this->year)),
            till: SerializableDateTime::fromString(sprintf('%d-12-31', $this->year)),
        );
    }

    public function getNumberOfDays(): int
    {
        return SerializableDateTime::fromString(sprintf('%d-01-01', $this->year))->format('L') ? 366 : 365;
    }

    public function getMonths(): Months
    {
        return Months::create(
            startDate: SerializableDateTime::fromString(sprintf('%d-01-01', $this->year)),
            endDate: SerializableDateTime::fromString(sprintf('%d-12-31', $this->year)),
        );
    }
}
