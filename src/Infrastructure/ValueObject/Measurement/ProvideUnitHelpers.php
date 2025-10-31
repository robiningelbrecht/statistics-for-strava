<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\Time\Minute;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;

trait ProvideUnitHelpers
{
    public const string KILOMETER = 'km';
    public const string METER = 'm';
    public const string MILES = 'mi';
    public const string FOOT = 'ft';
    public const string HOUR = 'hour';
    public const string MINUTE = 'minute';

    abstract public function getGoal(): Unit;

    public static function createUnitFromScalars(float $value, string $unit): Unit
    {
        return match ($unit) {
            self::KILOMETER => Kilometer::from($value),
            self::METER => Meter::from($value),
            self::MILES => Mile::from($value),
            self::FOOT => Foot::from($value),
            self::HOUR => Hour::from($value),
            self::MINUTE => Minute::from($value),
            default => throw new \InvalidArgumentException('Invalid unit '.$unit),
        };
    }

    public function convertKilometerToUnit(Kilometer $kilometer): Unit
    {
        if ($this->getGoal() instanceof Kilometer) {
            return $kilometer;
        }
        if ($this->getGoal() instanceof Meter) {
            return $kilometer->toMeter();
        }
        if ($this->getGoal() instanceof Mile) {
            return $kilometer->toMiles();
        }
        if ($this->getGoal() instanceof Foot) {
            return $kilometer->toMeter()->toFoot();
        }

        throw new \RuntimeException(sprintf('Cannot convert Kilometer to %s', $this->getGoal()::class));
    }

    public function convertMeterToUnit(Meter $meter): Unit
    {
        if ($this->getGoal() instanceof Meter) {
            return $meter;
        }
        if ($this->getGoal() instanceof Kilometer) {
            return $meter->toKilometer();
        }
        if ($this->getGoal() instanceof Mile) {
            return $meter->toKilometer()->toMiles();
        }
        if ($this->getGoal() instanceof Foot) {
            return $meter->toFoot();
        }

        throw new \RuntimeException(sprintf('Cannot convert Meter to %s', $this->getGoal()::class));
    }

    public function convertSecondsToUnit(Seconds $seconds): Unit
    {
        if ($this->getGoal() instanceof Hour) {
            return $seconds->toHour();
        }
        if ($this->getGoal() instanceof Minute) {
            return $seconds->toMinute();
        }

        throw new \RuntimeException(sprintf('Cannot convert Seconds to %s', $this->getGoal()::class));
    }
}
