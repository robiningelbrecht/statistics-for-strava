<?php

namespace App\Tests\Domain\Activity\Training\FindNumberOfRestDays;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDays;
use App\Domain\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDaysQueryHandler;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class FindNumberOfRestDaysQueryHandlerTest extends ContainerTestCase
{
    private FindNumberOfRestDaysQueryHandler $queryHandler;

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
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-04 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('5'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-03 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDaysResponse $response */
        $response = $this->queryHandler->handle(new FindNumberOfRestDays(DateRange::fromDates(
            from: SerializableDateTime::fromString('2025-01-01 00:00:00'),
            till: SerializableDateTime::fromString('2025-01-04 00:00:00')
        )));
        $this->assertEquals(
            2,
            $response->getNumberOfRestDays()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindNumberOfRestDaysQueryHandler($this->getConnection());
    }
}
