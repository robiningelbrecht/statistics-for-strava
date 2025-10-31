<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\Time\Minute;

trait ProvideUnitFromScalar
{
    public const string KILOMETER = 'km';
    public const string METER = 'm';
    public const string MILES = 'mi';
    public const string FOOT = 'ft';
    public const string HOUR = 'hour';
    public const string MINUTE = 'minute';

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
}
