<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Time;

use App\Domain\Calendar\Months;

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
            from: $this->getFrom(),
            till: $this->getTo(),
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

    public function getFrom(): SerializableDateTime
    {
        return SerializableDateTime::fromString(sprintf('%d-01-01', $this->year));
    }

    public function getTo(): SerializableDateTime
    {
        return SerializableDateTime::fromString(sprintf('%d-12-31', $this->year));
    }
}
