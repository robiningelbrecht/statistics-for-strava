<?php

namespace App\Tests\Domain\Activity\Lap\ImportActivityLaps;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Lap\ImportActivityLaps\ImportActivityLaps;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Lap\ActivityLapBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivityLapsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-laps-not-imported'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            Json::decode('{"laps":[{"id":52014888132,"resource_state":2,"name":"Lap 1","activity":{"id":14630387617,"visibility":"everyone","resource_state":1},"athlete":{"id":62214940,"resource_state":1},"elapsed_time":643,"moving_time":644,"start_date":"2025-05-29T10:15:42Z","start_date_local":"2025-05-29T12:15:42Z","distance":5010.93,"average_speed":7.78,"max_speed":19.74,"lap_index":1,"split":1,"start_index":0,"end_index":607,"total_elevation_gain":68.2,"average_cadence":87.8,"device_watts":true,"average_watts":175.6,"average_heartrate":162.2,"max_heartrate":171},{"id":52014888145,"resource_state":2,"name":"Lap 2","activity":{"id":14630387617,"visibility":"everyone","resource_state":1},"athlete":{"id":62214940,"resource_state":1},"elapsed_time":6076,"moving_time":6077,"start_date":"2025-05-29T10:26:26Z","start_date_local":"2025-05-29T12:26:26Z","distance":45126.6,"average_speed":7.43,"max_speed":19.88,"lap_index":2,"split":2,"start_index":608,"end_index":6672,"total_elevation_gain":828.8,"average_cadence":87.6,"device_watts":true,"average_watts":179.5,"average_heartrate":164.6,"max_heartrate":176}]}'),
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-laps-but-already-imported'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-10'))
                ->build(),
            Json::decode('{"laps":[{"id":52014888132,"resource_state":2,"name":"Lap 1","activity":{"id":14630387617,"visibility":"everyone","resource_state":1},"athlete":{"id":62214940,"resource_state":1},"elapsed_time":643,"moving_time":644,"start_date":"2025-05-29T10:15:42Z","start_date_local":"2025-05-29T12:15:42Z","distance":5010.93,"average_speed":7.78,"max_speed":19.74,"lap_index":1,"split":1,"start_index":0,"end_index":607,"total_elevation_gain":68.2,"average_cadence":87.8,"device_watts":true,"average_watts":175.6,"average_heartrate":162.2,"max_heartrate":171},{"id":52014888145,"resource_state":2,"name":"Lap 2","activity":{"id":14630387617,"visibility":"everyone","resource_state":1},"athlete":{"id":62214940,"resource_state":1},"elapsed_time":6076,"moving_time":6077,"start_date":"2025-05-29T10:26:26Z","start_date_local":"2025-05-29T12:26:26Z","distance":45126.6,"average_speed":7.43,"max_speed":19.88,"lap_index":2,"split":2,"start_index":608,"end_index":6672,"total_elevation_gain":828.8,"average_cadence":87.6,"device_watts":true,"average_watts":179.5,"average_heartrate":164.6,"max_heartrate":176}]}'),
        ));
        $this->getContainer()->get(ActivityLapRepository::class)->add(
            ActivityLapBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-laps-but-already-imported'))
                ->withLapNumber(3)
                ->build()
        );
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('without-laps'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-09'))
                ->build(),
            []
        ));

        $this->commandBus->dispatch(new ImportActivityLaps($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
