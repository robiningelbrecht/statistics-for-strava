<?php

namespace App\Tests\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams\Epsilon;
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
        $distanceStream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::DISTANCE)
            ->withData([0, 5, 10, 15, 20, 25, 30, 50, 75, 100, 125, 150, 160])
            ->build();
        $altitudeStream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::ALTITUDE)
            ->withData([10, 10.2, 10.5, 11, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
            ->build();
        $rdp = new RamerDouglasPeucker(
            distanceStream: $distanceStream,
            movingStream: ActivityStreamBuilder::fromDefaults()
                ->withStreamType(StreamType::MOVING)
                ->withData([true, true, true, true, true, true, true, true, true, true, true, true, false])
                ->build(),
            otherStreams: ActivityStreams::fromArray([
                $altitudeStream,
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

        $this->assertMatchesJsonSnapshot($rdp->applyWith(Epsilon::create(
            activityType: ActivityType::RIDE,
        )));
    }

    public function testApplyWithWeirdData(): void
    {
        $distanceStream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::DISTANCE)
            ->withData([0, 5, 10, 15, 20, 25, 30, 50, 75, 100, 125, 150, 180])
            ->build();
        $altitudeStream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::ALTITUDE)
            ->withData([10, 10.2, 10.5, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
            ->build();
        $rdp = new RamerDouglasPeucker(
            distanceStream: $distanceStream,
            movingStream: null,
            otherStreams: ActivityStreams::fromArray([
                $altitudeStream,
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
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::VELOCITY)
                    ->withData([150, 152, 155, 157, 160, 162, 165, 168, 170, 172, 175, 178, 0.499])
                    ->build(),
            ])
        );

        $this->assertMatchesJsonSnapshot($rdp->applyWith(Epsilon::create(
            activityType: ActivityType::RIDE,
        )));
    }

    public function testItShouldThrow(): void
    {
        $distanceStream = ActivityStreamBuilder::fromDefaults()
            ->withStreamType(StreamType::DISTANCE)
            ->withData([])
            ->build();
        $altitudeStream = ActivityStreamBuilder::fromDefaults()
               ->withStreamType(StreamType::ALTITUDE)
               ->withData([10, 10.2, 10.5, 11, 11.5, 12, 12.3, 13, 13.5, 14, 14.5, 15])
               ->build();
        $rdp = new RamerDouglasPeucker(
            distanceStream: $distanceStream,
            movingStream: null,
            otherStreams: ActivityStreams::fromArray([
                $altitudeStream,
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
        $rdp->applyWith(Epsilon::create(
            activityType: ActivityType::RIDE,
        ));
    }
}
