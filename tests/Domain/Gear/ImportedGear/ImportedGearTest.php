<?php

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use Money\Money;
use PHPUnit\Framework\TestCase;

class ImportedGearTest extends TestCase
{
    public function testGetMovingTimeFormatted(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(10000))
            ->build()
            ->withMovingTime(Seconds::from(3661));

        $this->assertEquals('1h 1m', $gear->getMovingTimeFormatted());
    }

    public function testGetMovingTimeFormattedWithZero(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()->build();

        $this->assertEquals('0m', $gear->getMovingTimeFormatted());
    }

    public function testGetMovingTimeInHours(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withMovingTime(Seconds::from(7200));

        $this->assertEquals(2.0, $gear->getMovingTimeInHours()->toFloat());
    }

    public function testGetAverageDistance(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(30000))
            ->build()
            ->withNumberOfActivities(3);

        $this->assertEquals(Kilometer::from(10), $gear->getAverageDistance());
    }

    public function testGetAverageDistanceWithZeroActivities(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(30000))
            ->build();

        $this->assertEquals(Kilometer::zero(), $gear->getAverageDistance());
    }

    public function testGetAverageSpeed(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(10000))
            ->build()
            ->withMovingTime(Seconds::from(3600));

        $this->assertEquals(KmPerHour::from(10), $gear->getAverageSpeed());
    }

    public function testGetAverageSpeedWithZeroMovingTime(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withDistanceInMeter(Meter::from(10000))
            ->build();

        $this->assertEquals(KmPerHour::zero(), $gear->getAverageSpeed());
    }

    public function testGetRelativeCostPerHour(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withMovingTime(Seconds::from(7200))
            ->withPurchasePrice(Money::EUR(10000));

        $this->assertEquals(Money::EUR(5000), $gear->getRelativeCostPerHour());
    }

    public function testGetRelativeCostPerHourWithZeroMovingTime(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withPurchasePrice(Money::EUR(10000));

        $this->assertEquals(Money::EUR(10000), $gear->getRelativeCostPerHour());
    }

    public function testGetRelativeCostPerHourWithoutPurchasePrice(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withMovingTime(Seconds::from(7200));

        $this->assertNull($gear->getRelativeCostPerHour());
    }

    public function testGetRelativeCostPerWorkout(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withNumberOfActivities(5)
            ->withPurchasePrice(Money::EUR(10000));

        $this->assertEquals(Money::EUR(2000), $gear->getRelativeCostPerWorkout());
    }

    public function testGetRelativeCostPerWorkoutWithZeroActivities(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withPurchasePrice(Money::EUR(10000));

        $this->assertEquals(Money::EUR(10000), $gear->getRelativeCostPerWorkout());
    }

    public function testGetRelativeCostPerWorkoutWithoutPurchasePrice(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->build()
            ->withNumberOfActivities(5);

        $this->assertNull($gear->getRelativeCostPerWorkout());
    }
}
