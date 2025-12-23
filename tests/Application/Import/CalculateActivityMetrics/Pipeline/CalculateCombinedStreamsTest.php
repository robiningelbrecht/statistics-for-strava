<?php

namespace App\Tests\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateCombinedStreams;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateCombinedStreamsTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CalculateCombinedStreams $calculateCombinedStreams;

    public function testProcess(): void
    {
        $activityWithRawDataRepository = $this->getContainer()->get(ActivityWithRawDataRepository::class);
        $streamRepository = $this->getContainer()->get(ActivityStreamRepository::class);
        $output = new SpyOutput();

        for ($i = 5; $i <= 20; ++$i) {
            $activityWithRawDataRepository->add(
                ActivityWithRawData::fromState(
                    ActivityBuilder::fromDefaults()
                        ->withActivityId(ActivityId::fromUnprefixed($i))
                        ->build(),
                    []
                )
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::DISTANCE)
                    ->withData([1])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::ALTITUDE)
                    ->withData([2])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::CADENCE)
                    ->withData([3])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::WATTS)
                    ->withData([])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::TIME)
                    ->withData([3])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::MOVING)
                    ->withData([true])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::VELOCITY)
                    ->withData([3])
                    ->build()
            );
            $streamRepository->add(
                ActivityStreamBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed($i))
                    ->withStreamType(StreamType::LAT_LNG)
                    ->withData([[1, 2]])
                    ->build()
            );
        }

        $this->calculateCombinedStreams->process($output);

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );

        $this->calculateCombinedStreams->process($output);
        $this->assertMatchesTextSnapshot($output);
    }

    public function testProcessImperial(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                    ->withSportType(SportType::RUN)
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([1])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([2])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::CADENCE)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::WATTS)
                ->withData([])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::TIME)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::VELOCITY)
                ->withData([3])
                ->build()
        );

        new CalculateCombinedStreams(
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            combinedActivityStreamRepository: $this->getContainer()->get(CombinedActivityStreamRepository::class),
            activityStreamRepository: $this->getContainer()->get(ActivityStreamRepository::class),
            unitSystem: UnitSystem::IMPERIAL,
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            )
        )->process($output);

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );
    }

    public function testProcessWithEmptyDistanceStream(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([2])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::CADENCE)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::WATTS)
                ->withData([])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::TIME)
                ->withData([3])
                ->build()
        );

        $this->calculateCombinedStreams->process($output);

        $this->assertEmpty(
            $this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative()
        );
    }

    public function testProcessForRunWithVelocity(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                    ->withSportType(SportType::RUN)
                    ->build(),
                []
            )
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::DISTANCE)
                ->withData([1])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::ALTITUDE)
                ->withData([2])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::CADENCE)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::WATTS)
                ->withData([])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::TIME)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::VELOCITY)
                ->withData([3])
                ->build()
        );

        $this->calculateCombinedStreams->process($output);

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );

        $this->calculateCombinedStreams->process($output);
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->calculateCombinedStreams = $this->getContainer()->get(CalculateCombinedStreams::class);
        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test"}']
        );
    }
}
