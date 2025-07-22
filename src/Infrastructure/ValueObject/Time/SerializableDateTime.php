<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Time;

use Carbon\Carbon;

class SerializableDateTime extends \DateTimeImmutable implements \JsonSerializable, \Stringable
{
    public static function fromDateTimeImmutable(\DateTimeImmutable $date): self
    {
        return self::fromString($date->format('Y-m-d H:i:s'));
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public static function fromTimestamp(int $unixTimestamp): self
    {
        return self::fromString('now')->setTimestamp($unixTimestamp);
    }

    public static function fromYearAndWeekNumber(int $year, int $weekNumber): self
    {
        $datetime = (new self())->setISODate($year, $weekNumber);

        return self::fromString(
            $datetime->format('Y-m-d H:i:s')
        );
    }

    public static function createFromFormat(string $format, string $datetime, ?\DateTimeZone $timezone = null): self
    {
        if (!$datetime = parent::createFromFormat($format, $datetime, $timezone)) {
            throw new \InvalidArgumentException(sprintf('Invalid date format %s for %s', $format, $datetime));
        }

        return self::fromString(
            $datetime->format('Y-m-d H:i:s'),
        );
    }

    public static function some(): self
    {
        return self::fromString('2025-01-01 00:00:00');
    }

    public function __toString(): string
    {
        return $this->iso();
    }

    public function jsonSerialize(): string
    {
        return $this->iso();
    }

    public function iso(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function translatedFormat(string $format): string
    {
        return new Carbon($this)->translatedFormat($format);
    }

    public function toUtc(): self
    {
        return $this->toTimezone(SerializableTimezone::UTC());
    }

    public function toTimezone(SerializableTimezone $timezone): self
    {
        return $this->setTimezone($timezone);
    }

    public function getHourWithoutLeadingZero(): int
    {
        return (int) $this->format('G');
    }

    public function getMinutesWithoutLeadingZero(): int
    {
        return intval($this->format('i'));
    }

    public function getMonthWithoutLeadingZero(): int
    {
        return intval($this->format('n'));
    }

    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    public function getWeekNumber(): int
    {
        $weekNumber = (int) $this->format('W');
        if (1 === $weekNumber && 12 === $this->getMonthWithoutLeadingZero()) {
            $weekNumber = 52;
        }
        if (53 === $weekNumber && 12 === $this->getMonthWithoutLeadingZero()) {
            $weekNumber = 52;
        }
        if (53 === $weekNumber && 1 === $this->getMonthWithoutLeadingZero()) {
            $weekNumber = 1;
        }

        return $weekNumber;
    }

    /**
     * @return int[]
     */
    public function getYearAndWeekNumber(): array
    {
        return [$this->getYear(), $this->getWeekNumber()];
    }

    public function getYearAndWeekNumberString(): string
    {
        return implode('-', $this->getYearAndWeekNumber());
    }

    public function isAfterOrOn(SerializableDateTime $that): bool
    {
        return $this >= $that;
    }

    public function isBeforeOrOn(SerializableDateTime $that): bool
    {
        return $this <= $that;
    }

    public function isBefore(SerializableDateTime $that): bool
    {
        return $this < $that;
    }

    public function isAfter(SerializableDateTime $that): bool
    {
        return $this > $that;
    }
}
