<?php

namespace App\Tests\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams\RamerDouglasPeucker;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RamerDouglasPeuckerTest extends TestCase
{
    use MatchesSnapshots;

    public function testApply(): void
    {
        $rdp = new RamerDouglasPeucker(
            ActivityType::RIDE,
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::DISTANCE)
                ->withData([0, 5, 10, 15, 20, 25, 30, 50, 75, 100, 125, 150])
                ->build(),
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([10, 10.2, 10.5, 11, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
                ->build(),
            ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::CADENCE)
                    ->withData([90, 88, 87, 85, 83, 80, 78, 75, 72, 70, 68, 65])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::WATTS)
                    ->withData([250, 255, 260, 270, 275, 280, 290, 300, 310, 320, 330, 340])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::HEART_RATE)
                    ->withData([150, 152, 155, 157, 160, 162, 165, 168, 170, 172, 175, 178])
                    ->build(),
            ])
        );

        $this->assertMatchesJsonSnapshot($rdp->apply());
    }

    public function testApplyWithWeirdData(): void
    {
        $rdp = new RamerDouglasPeucker(
            ActivityType::RIDE,
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::DISTANCE)
                ->withData([0, 5, 10, 15, 20, 25, 30, 50, 75, 100, 125, 150])
                ->build(),
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([10, 10.2, 10.5, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
                ->build(),
            ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::CADENCE)
                    ->withData([90, 88, 87, 85, 83, 80, 78, 75, 72, 70, 68])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::WATTS)
                    ->withData([250, 255, 260, 270, 275, 280, 290, 300, 310, 320, 330, 340])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::HEART_RATE)
                    ->withData([150, 152, 155, 157, 160, 162, 165, 168, 170, 172, 175, 178])
                    ->build(),
            ])
        );

        $this->assertMatchesJsonSnapshot($rdp->apply());
    }

    public function testItShouldThrow(): void
    {
        $rdp = new RamerDouglasPeucker(
            ActivityType::RIDE,
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::DISTANCE)
                ->withData([])
                ->build(),
            ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([10, 10.2, 10.5, 11, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
                ->build(),
            ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::CADENCE)
                    ->withData([90, 88, 87, 85, 83, 80, 78, 75, 72, 70, 68, 65])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::WATTS)
                    ->withData([250, 255, 260, 270, 275, 280, 290, 300, 310, 320, 330, 340])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::HEART_RATE)
                    ->withData([150, 152, 155, 157, 160, 162, 165, 168, 170, 172, 175, 178])
                    ->build(),
            ])
        );

        $this->expectExceptionObject(new \InvalidArgumentException('Distance stream is empty'));
        $rdp->apply();
    }
}
