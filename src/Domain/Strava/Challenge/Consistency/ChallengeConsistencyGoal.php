<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\Time\Minute;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class ChallengeConsistencyGoal implements Unit
{
    private function __construct(
        private Unit $unit,
    ) {
    }

    public static function from(float $value, ?string $unit = null): self
    {
        return match ($unit) {
            'km' => new self(Kilometer::from($value)),
            'm' => new self(Meter::from($value)),
            'mi' => new self(Mile::from($value)),
            'ft' => new self(Foot::from($value)),
            'hour' => new self(Hour::from($value)),
            'minute' => new self(Minute::from($value)),
            null => throw new \InvalidArgumentException('$unit cannot be empty'),
            default => throw new \InvalidArgumentException('Invalid unit '.$unit),
        };
    }

    public static function zero(?string $unit = null): self
    {
        return self::from(
            value: 0,
            unit: $unit
        );
    }

    public function isZeroOrLower(): bool
    {
        return $this->unit->isZeroOrLower();
    }

    public function isLowerThanOne(): bool
    {
        return $this->unit->isLowerThanOne();
    }

    public function getSymbol(): string
    {
        return $this->unit->getSymbol();
    }

    public function toFloat(): float
    {
        return $this->unit->toFloat();
    }

    public function toInt(): int
    {
        return $this->unit->toInt();
    }

    public function __toString(): string
    {
        return (string) $this->unit;
    }

    public function jsonSerialize(): float
    {
        return $this->unit->toFloat();
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function convertKilometerToUnit(Kilometer $kilometer): Unit
    {
        if ($this->getUnit() instanceof Kilometer) {
            return $kilometer;
        }
        if ($this->getUnit() instanceof Meter) {
            return $kilometer->toMeter();
        }
        if ($this->getUnit() instanceof Mile) {
            return $kilometer->toMiles();
        }
        if ($this->getUnit() instanceof Foot) {
            return $kilometer->toMeter()->toFoot();
        }

        throw new \RuntimeException(sprintf('Cannot convert Kilometer to %s', $this->getUnit()::class));
    }

    public function convertMeterToUnit(Meter $meter): Unit
    {
        if ($this->getUnit() instanceof Meter) {
            return $meter;
        }
        if ($this->getUnit() instanceof Kilometer) {
            return $meter->toKilometer();
        }
        if ($this->getUnit() instanceof Mile) {
            return $meter->toKilometer()->toMiles();
        }
        if ($this->getUnit() instanceof Foot) {
            return $meter->toFoot();
        }

        throw new \RuntimeException(sprintf('Cannot convert Meter to %s', $this->getUnit()::class));
    }

    public function convertSecondsToUnit(Seconds $seconds): Unit
    {
        if ($this->getUnit() instanceof Hour) {
            return $seconds->toHour();
        }
        if ($this->getUnit() instanceof Minute) {
            return $seconds->toMinute();
        }

        throw new \RuntimeException(sprintf('Cannot convert Seconds to %s', $this->getUnit()::class));
    }
}
