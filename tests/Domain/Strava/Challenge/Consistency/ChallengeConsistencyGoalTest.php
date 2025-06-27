<?php

namespace App\Tests\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Challenge\Consistency\ChallengeConsistencyGoal;
use App\Infrastructure\ValueObject\Measurement\Length\Foot;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Length\Mile;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\Time\Minute;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Measurement\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChallengeConsistencyGoalTest extends TestCase
{
    #[DataProvider(methodName: 'provideData')]
    public function testFrom(string $unitName, Unit $expectedDecoratedUnit): void
    {
        $this->assertEquals(
            $expectedDecoratedUnit,
            ChallengeConsistencyGoal::zero($unitName)->getUnit()
        );
    }

    public function testFromItShouldThrowWhenNull(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('$unit cannot be empty'));

        ChallengeConsistencyGoal::from(0);
    }

    public function testFromItShouldThrowWhenInvalidUnit(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid unit gram'));

        ChallengeConsistencyGoal::from(0, 'gram');
    }

    #[DataProvider(methodName: 'provideDataForUnitConversion')]
    public function testConvertKilometerToUnit(ChallengeConsistencyGoal $goal, Unit $expectedConversion): void
    {
        $this->assertEquals(
            $expectedConversion,
            $goal->convertKilometerToUnit(Kilometer::zero())
        );
    }

    public function testConvertKilometerToUnitItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Cannot convert Kilometer to App\Infrastructure\ValueObject\Measurement\Time\Hour'));
        ChallengeConsistencyGoal::from(0, 'hour')->convertKilometerToUnit(Kilometer::zero());
    }

    #[DataProvider(methodName: 'provideDataForUnitConversion')]
    public function testConvertMeterToUnit(ChallengeConsistencyGoal $goal, Unit $expectedConversion): void
    {
        $this->assertEquals(
            $expectedConversion,
            $goal->convertMeterToUnit(Meter::zero())
        );
    }

    public function testConvertMeterToUnitItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Cannot convert Meter to App\Infrastructure\ValueObject\Measurement\Time\Hour'));
        ChallengeConsistencyGoal::from(0, 'hour')->convertMeterToUnit(Meter::zero());
    }

    #[DataProvider(methodName: 'provideDataForSecondsToUnitConversion')]
    public function testConvertSecondsToUnit(ChallengeConsistencyGoal $goal, Unit $expectedConversion): void
    {
        $this->assertEquals(
            $expectedConversion,
            $goal->convertSecondsToUnit(Seconds::zero())
        );
    }

    public function testConvertSecondsToUnitItShouldThrow(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Cannot convert Seconds to App\Infrastructure\ValueObject\Measurement\Length\Meter'));
        ChallengeConsistencyGoal::from(0, 'm')->convertSecondsToUnit(Seconds::zero());
    }

    public static function provideData(): iterable
    {
        yield 'kilometer' => [
            'km',
            Kilometer::zero(),
        ];

        yield 'meter' => [
            'm',
            Meter::zero(),
        ];

        yield 'mile' => [
            'mi',
            Mile::zero(),
        ];

        yield 'foot' => [
            'ft',
            Foot::zero(),
        ];

        yield 'hour' => [
            'hour',
            Hour::zero(),
        ];

        yield 'minute' => [
            'minute',
            Minute::zero(),
        ];
    }

    public static function provideDataForUnitConversion(): iterable
    {
        yield 'kilometer' => [
            ChallengeConsistencyGoal::from(0, 'km'),
            Kilometer::from(0),
        ];
        yield 'meter' => [
            ChallengeConsistencyGoal::from(0, 'm'),
            Meter::from(0),
        ];
        yield 'miles' => [
            ChallengeConsistencyGoal::from(0, 'mi'),
            Mile::from(0),
        ];
        yield 'foot' => [
            ChallengeConsistencyGoal::from(0, 'ft'),
            Foot::from(0),
        ];
    }

    public static function provideDataForSecondsToUnitConversion(): iterable
    {
        yield 'hour' => [
            ChallengeConsistencyGoal::from(0, 'hour'),
            Hour::from(0),
        ];
        yield 'minute' => [
            ChallengeConsistencyGoal::from(0, 'minute'),
            Minute::from(0),
        ];
    }
}
