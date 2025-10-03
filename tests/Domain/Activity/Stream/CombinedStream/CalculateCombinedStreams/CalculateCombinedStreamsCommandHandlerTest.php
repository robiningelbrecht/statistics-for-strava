<?php

namespace App\Tests\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams\CalculateCombinedStreams;
use App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams\CalculateCombinedStreamsCommandHandler;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateCombinedStreamsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandleMetric(): void
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
                ->withStreamType(StreamType::MOVING)
                ->withData([true])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::VELOCITY)
                ->withData([3])
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-5'))
                ->withStreamType(StreamType::LAT_LNG)
                ->withData([[1, 2]])
                ->build()
        );

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));
        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleImperial(): void
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

        new CalculateCombinedStreamsCommandHandler(
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(CombinedActivityStreamRepository::class),
            $this->getContainer()->get(ActivityStreamRepository::class),
            UnitSystem::IMPERIAL,
        )->handle(new CalculateCombinedStreams($output));

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );
    }

    public function testHandleWithEmptyDistanceStream(): void
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

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));

        $this->assertEmpty(
            $this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative()
        );

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));
        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleForRunWithVelocity(): void
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

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM CombinedActivityStream')->fetchAllAssociative())
        );

        $this->commandBus->dispatch(new CalculateCombinedStreams($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
