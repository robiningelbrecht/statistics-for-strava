<?php

declare(strict_types=1);

namespace App\Tests\Application\Build\BuildActivitiesHtml;

use App\Domain\Activity\Gap\ActivityGapAssembler;
use App\Domain\Activity\Split\ActivitySplits;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Split\ActivitySplitBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use PHPUnit\Framework\TestCase;

final class BuildActivityGapAssemblerTest extends TestCase
{
    public function testItReturnsNullForNonRunningActivities(): void
    {
        $assembler = new ActivityGapAssembler();

        self::assertNull($assembler->for(
            activity: ActivityBuilder::fromDefaults()->withSportType(SportType::RIDE)->build(),
            streams: ActivityStreams::empty(),
            metricSplits: ActivitySplits::empty(),
            imperialSplits: ActivitySplits::empty(),
        ));
    }

    public function testItReturnsNullWhenRequiredStreamsAreMissing(): void
    {
        $assembler = new ActivityGapAssembler();

        self::assertNull($assembler->for(
            activity: ActivityBuilder::fromDefaults()->withSportType(SportType::RUN)->build(),
            streams: ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::TIME)
                    ->withData([0, 60, 120])
                    ->build(),
            ]),
            metricSplits: ActivitySplits::empty(),
            imperialSplits: ActivitySplits::empty(),
        ));
    }

    public function testItMapsGapOntoImportedSplitRowsAndFiltersPausedSamples(): void
    {
        $assembler = new ActivityGapAssembler();

        $gap = $assembler->for(
            activity: ActivityBuilder::fromDefaults()->withSportType(SportType::RUN)->build(),
            streams: ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::LAT_LNG)
                    ->withData([
                        [51.0000, 4.0000],
                        [51.0045, 4.0000],
                        [51.0090, 4.0000],
                        [51.0135, 4.0000],
                        [51.0180, 4.0000],
                    ])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::ALTITUDE)
                    ->withData([10.0, 30.0, 30.0, 40.0, 20.0])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::TIME)
                    ->withData([0, 300, 600, 900, 1200])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::MOVING)
                    ->withData([true, true, false, true, true])
                    ->build(),
            ]),
            metricSplits: ActivitySplits::fromArray([
                ActivitySplitBuilder::fromDefaults()
                    ->withUnitSystem(UnitSystem::METRIC)
                    ->withSplitNumber(1)
                    ->withDistanceInMeter(1000)
                    ->build(),
                ActivitySplitBuilder::fromDefaults()
                    ->withUnitSystem(UnitSystem::METRIC)
                    ->withSplitNumber(2)
                    ->withDistanceInMeter(1000)
                    ->build(),
            ]),
            imperialSplits: ActivitySplits::fromArray([
                ActivitySplitBuilder::fromDefaults()
                    ->withUnitSystem(UnitSystem::IMPERIAL)
                    ->withSplitNumber(1)
                    ->withDistanceInMeter(1609.344)
                    ->build(),
            ]),
        );

        self::assertNotNull($gap);
        self::assertGreaterThan(0.0, $gap->getOverallGapPaceInSecondsPerKm()->toFloat());
        self::assertNotNull($gap->getSplit(UnitSystem::METRIC, 1));
        self::assertNotNull($gap->getSplit(UnitSystem::METRIC, 2));
        self::assertNull($gap->getSplit(UnitSystem::METRIC, 3));
        self::assertNotNull($gap->getSplit(UnitSystem::IMPERIAL, 1));
        self::assertNull($gap->getSplit(UnitSystem::IMPERIAL, 2));
    }

    public function testItDoesNotCreateSyntheticSplitDataWhenImportedSplitsRunOut(): void
    {
        $assembler = new ActivityGapAssembler();

        $gap = $assembler->for(
            activity: ActivityBuilder::fromDefaults()->withSportType(SportType::RUN)->build(),
            streams: ActivityStreams::fromArray([
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::LAT_LNG)
                    ->withData([
                        [51.0000, 4.0000],
                        [51.0090, 4.0000],
                        [51.0180, 4.0000],
                    ])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::ALTITUDE)
                    ->withData([10.0, 20.0, 10.0])
                    ->build(),
                ActivityStreamBuilder::fromDefaults()
                    ->withStreamType(StreamType::TIME)
                    ->withData([0, 600, 1200])
                    ->build(),
            ]),
            metricSplits: ActivitySplits::fromArray([
                ActivitySplitBuilder::fromDefaults()
                    ->withUnitSystem(UnitSystem::METRIC)
                    ->withSplitNumber(1)
                    ->withDistanceInMeter(1000)
                    ->build(),
            ]),
            imperialSplits: ActivitySplits::empty(),
        );

        self::assertNotNull($gap);
        self::assertNotNull($gap->getSplit(UnitSystem::METRIC, 1));
        self::assertNull($gap->getSplit(UnitSystem::METRIC, 2));
    }
}
