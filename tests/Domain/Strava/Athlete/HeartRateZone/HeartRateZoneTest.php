<?php

namespace App\Tests\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HeartRateZoneTest extends TestCase
{
    #[DataProvider(methodName: 'provideTestData')]
    public function testGetMinMaxRange(int $zone, int $athleteMaxHeartRate, array $expectedRange): void
    {
        $this->assertEquals(
            $expectedRange,
            HeartRateZone::from($zone)->getMinMaxRange($athleteMaxHeartRate)
        );
    }

    public static function provideTestData(): array
    {
        return [
            [1, 100, [0, 60]],
            [2, 100, [61, 70]],
            [3, 100, [71, 80]],
            [4, 100, [81, 90]],
            [5, 100, [91, 10000]],
            [1, 150, [0, 90]],
            [2, 150, [91, 105]],
            [3, 150, [106, 120]],
            [4, 150, [121, 135]],
            [5, 150, [136, 10000]],
            [1, 186, [0, 111]],
            [2, 186, [112, 130]],
            [3, 186, [131, 148]],
            [4, 186, [149, 167]],
            [5, 186, [168, 10000]],
        ];
    }
}
