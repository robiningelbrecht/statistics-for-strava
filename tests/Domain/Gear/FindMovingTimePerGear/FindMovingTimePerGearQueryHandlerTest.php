<?php

namespace App\Tests\Domain\Gear\FindMovingTimePerGear;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGear;
use App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGearQueryHandler;
use App\Domain\Gear\GearId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class FindMovingTimePerGearQueryHandlerTest extends ContainerTestCase
{
    private FindMovingTimePerGearQueryHandler $queryHandler;

    public function testHandle(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId(GearId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2023-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed('2'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('8'))
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGearResponse $response */
        $response = $this->queryHandler->handle(new FindMovingTimePerGear(Years::fromArray([Year::fromInt(2024)]), null));
        $this->assertEquals(
            [
                'gear-2' => 10,
                'gear-5' => 20,
            ],
            $response->getMovingTimePerGear()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindMovingTimePerGearQueryHandler($this->getConnection());
    }
}
