<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\MilesPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerMile;

enum UnitSystem: string
{
    case METRIC = 'metric';
    case IMPERIAL = 'imperial';

    public function distance(float $value): Kilometer|Mile
    {
        if (UnitSystem::METRIC === $this) {
            return Kilometer::from($value);
        }

        return Mile::from($value);
    }

    public function distanceSymbol(): string
    {
        return $this->distance(1)->getSymbol();
    }

    public function elevation(float $value): Meter|Foot
    {
        if (UnitSystem::METRIC === $this) {
            return Meter::from($value);
        }

        return Foot::from($value);
    }

    public function elevationSymbol(): string
    {
        return $this->elevation(1)->getSymbol();
    }

    public function speed(float $value): KmPerHour|MilesPerHour
    {
        if (UnitSystem::METRIC === $this) {
            return KmPerHour::from($value);
        }

        return MilesPerHour::from($value);
    }

    public function weight(float $value): Kilogram|Pound
    {
        if (UnitSystem::METRIC === $this) {
            return Kilogram::from($value);
        }

        return Pound::from($value);
    }

    public function carbonSavedSymbol(): string
    {
        return sprintf('%s CO2', $this->weight(1)->getSymbol());
    }

    public function pace(float $value): SecPerKm|SecPerMile
    {
        if (UnitSystem::METRIC === $this) {
            return SecPerKm::from($value);
        }

        return SecPerMile::from($value);
    }

    public function paceSymbol(): string
    {
        if (UnitSystem::METRIC === $this) {
            return '/km';
        }

        return '/mi';
    }
}
