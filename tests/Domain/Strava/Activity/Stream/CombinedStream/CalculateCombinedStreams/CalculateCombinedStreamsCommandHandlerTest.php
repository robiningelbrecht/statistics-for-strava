<?php

namespace App\Tests\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams\CalculateCombinedStreams;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
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
