<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Gap;

use App\Domain\Activity\Gap\GapCalculator;
use App\Domain\Activity\SportType\SportType;
use PHPUnit\Framework\TestCase;

final class GapCalculatorTest extends TestCase
{
    public function testItCalculatesGapSummaryFromGeneratorInput(): void
    {
        $calculator = new GapCalculator(smoothingWindowSize: 3);

        $summary = $calculator->calculate($this->trackPointsWithModerateClimb());

        self::assertSame(3, $summary['segments']);
        self::assertGreaterThan(330.0, $summary['distance_m']);
        self::assertLessThan(336.0, $summary['distance_m']);
        self::assertSame(120, $summary['duration_s']);
        self::assertGreaterThan(350.0, $summary['actual_pace_sec_per_km']);
        self::assertLessThan(370.0, $summary['actual_pace_sec_per_km']);
        self::assertLessThan($summary['actual_pace_sec_per_km'], $summary['gap_pace_sec_per_km']);
        self::assertGreaterThan(0.0, $summary['average_grade']);
        self::assertGreaterThan($summary['distance_m'], $summary['total_adjusted_distance_m']);
    }

    public function testItRewardsSlightDownhillButPenalizesSteepDownhill(): void
    {
        $calculator = new GapCalculator(smoothingWindowSize: 1);

        $segments = iterator_to_array($calculator->calculateSegments($this->downhillTrackPoints()), false);

        self::assertCount(2, $segments);
        self::assertLessThan(1.0, $segments[0]['gap_multiplier']);
        self::assertGreaterThan(1.0, $segments[1]['gap_multiplier']);
        self::assertGreaterThan($segments[1]['gap_pace_sec_per_km'], $segments[0]['gap_pace_sec_per_km']);
    }

    public function testItSkipsZeroDistanceAndNonIncreasingTimestamps(): void
    {
        $calculator = new GapCalculator(smoothingWindowSize: 1);

        $segments = iterator_to_array($calculator->calculateSegments([
            [
                'lat' => 51.0,
                'lon' => 4.0,
                'ele' => 10.0,
                'timestamp' => 1700000000,
            ],
            [
                'lat' => 51.0,
                'lon' => 4.0,
                'ele' => 20.0,
                'timestamp' => 1700000030,
            ],
            [
                'lat' => 51.0009,
                'lon' => 4.0,
                'ele' => 25.0,
                'timestamp' => 1700000030,
            ],
            [
                'lat' => 51.0018,
                'lon' => 4.0,
                'ele' => 30.0,
                'timestamp' => 1700000060,
            ],
        ]), false);

        self::assertCount(1, $segments);
        self::assertSame(30, $segments[0]['duration_s']);
    }

    public function testItOnlyAppliesToRunningSportTypes(): void
    {
        $calculator = new GapCalculator();

        self::assertTrue($calculator->supports(SportType::RUN));
        self::assertTrue($calculator->supports(SportType::TRAIL_RUN));
        self::assertTrue($calculator->supports(SportType::VIRTUAL_RUN));
        self::assertFalse($calculator->supports(SportType::RIDE));

        self::assertSame(
            [
                'segments' => 0,
                'distance_m' => 0.0,
                'duration_s' => 0,
                'actual_pace_sec_per_km' => null,
                'gap_pace_sec_per_km' => null,
                'average_grade' => 0.0,
                'total_adjusted_distance_m' => 0.0,
            ],
            $calculator->calculate($this->trackPointsWithModerateClimb(), SportType::RIDE)
        );
        self::assertSame(
            [],
            iterator_to_array($calculator->calculateSegments($this->trackPointsWithModerateClimb(), SportType::RIDE), false)
        );
    }

    /**
     * @return \Generator<int, array<string, float|int|string>>
     */
    private function trackPointsWithModerateClimb(): \Generator
    {
        yield [
            'lat' => 51.0000,
            'lon' => 4.0000,
            'ele' => 12.0,
            'timestamp' => '2026-04-18T08:00:00+00:00',
        ];
        yield [
            'lat' => 51.0010,
            'lon' => 4.0000,
            'ele' => 16.0,
            'timestamp' => '2026-04-18T08:00:40+00:00',
        ];
        yield [
            'lat' => 51.0020,
            'lon' => 4.0000,
            'ele' => 18.0,
            'timestamp' => '2026-04-18T08:01:20+00:00',
        ];
        yield [
            'lat' => 51.0030,
            'lon' => 4.0000,
            'ele' => 23.0,
            'timestamp' => '2026-04-18T08:02:00+00:00',
        ];
    }

    /**
     * @return list<array<string, float|int>>
     */
    private function downhillTrackPoints(): array
    {
        return [
            [
                'lat' => 51.0000,
                'lon' => 4.0000,
                'ele' => 30.0,
                'timestamp' => 1700000000,
            ],
            [
                'lat' => 51.0010,
                'lon' => 4.0000,
                'ele' => 19.0,
                'timestamp' => 1700000040,
            ],
            [
                'lat' => 51.0020,
                'lon' => 4.0000,
                'ele' => -41.0,
                'timestamp' => 1700000080,
            ],
        ];
    }
}
