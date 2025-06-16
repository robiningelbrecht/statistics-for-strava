<?php

namespace App\Tests\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZone;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HeartRateZoneTest extends TestCase
{
    #[DataProvider(methodName: 'provideTestRangeInBpmData')]
    public function testGetRangeInBpm(int $from, ?int $to, int $athleteMaxHeartRate, HeartRateZoneMode $mode, array $expectedRange): void
    {
        $this->assertEquals(
            $expectedRange,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: $mode,
                from: $from,
                to: $to,
            )->getRangeInBpm($athleteMaxHeartRate),
        );
    }

    public function testGetFromPercentage(): void
    {
        $this->assertEquals(
            60,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: HeartRateZoneMode::RELATIVE,
                from: 60,
                to: 80,
            )->getFromPercentage(100),
        );

        $this->assertEquals(
            55,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: HeartRateZoneMode::ABSOLUTE,
                from: 60,
                to: 80,
            )->getFromPercentage(110),
        );
    }

    public function testGetToPercentage(): void
    {
        $this->assertEquals(
            10000,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: HeartRateZoneMode::RELATIVE,
                from: 60,
                to: null,
            )->getToPercentage(100),
        );

        $this->assertEquals(
            80,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: HeartRateZoneMode::RELATIVE,
                from: 60,
                to: 80,
            )->getToPercentage(100),
        );

        $this->assertEquals(
            73,
            new HeartRateZone(
                name: HeartRateZone::FIVE,
                mode: HeartRateZoneMode::ABSOLUTE,
                from: 60,
                to: 80,
            )->getToPercentage(110),
        );
    }

    public static function provideTestRangeInBpmData(): array
    {
        return [
            // Relative easy numbers.
            [0, 60, 100, HeartRateZoneMode::RELATIVE, [0, 60]],
            [61, 70, 100, HeartRateZoneMode::RELATIVE, [61, 70]],
            [71, 80, 100, HeartRateZoneMode::RELATIVE, [71, 80]],
            [81, 90, 100, HeartRateZoneMode::RELATIVE, [81, 90]],
            [91, null, 100, HeartRateZoneMode::RELATIVE, [91, 10000]],
            // Relative real life example.
            [0, 60, 185, HeartRateZoneMode::RELATIVE, [0, 111]],
            [61, 70, 185, HeartRateZoneMode::RELATIVE, [112, 130]],
            [71, 80, 185, HeartRateZoneMode::RELATIVE, [131, 148]],
            [81, 90, 185, HeartRateZoneMode::RELATIVE, [149, 167]],
            [91, null, 185, HeartRateZoneMode::RELATIVE, [168, 18500]],
            // Relative real life example with custom zones.
            [0, 63, 185, HeartRateZoneMode::RELATIVE, [0, 117]],
            [64, 72, 185, HeartRateZoneMode::RELATIVE, [118, 134]],
            [73, 88, 185, HeartRateZoneMode::RELATIVE, [135, 163]],
            [89, 95, 185, HeartRateZoneMode::RELATIVE, [164, 176]],
            [96, null, 185, HeartRateZoneMode::RELATIVE, [177, 18500]],
            // Absolute
            [82, 95, 120, HeartRateZoneMode::ABSOLUTE, [82, 95]],
            [82, 95, 140, HeartRateZoneMode::ABSOLUTE, [82, 95]],
        ];
    }
}
