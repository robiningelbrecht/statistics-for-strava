<?php

namespace App\Tests\Domain\Strava\Rewind\FindAvailableRewindYears;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYears;
use App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYearsQueryHandler;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class FindAvailableRewindYearsQueryHandlerTest extends ContainerTestCase
{
    private FindAvailableRewindYearsQueryHandler $queryHandler;

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
                ->withStartDateTime(SerializableDateTime::fromString('2023-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYearsResponse $response */
        $response = $this->queryHandler->handle(
            new FindAvailableRewindYears(SerializableDateTime::fromString('2025-01-01 00:00:00'))
        );

        $this->assertEquals(
            Years::fromArray([Year::fromInt(2024), Year::fromInt(2023)]),
            $response->getAvailableRewindYears(),
        );
    }

    public function testHandleWhenAfterCutOffDate(): void
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
                ->withStartDateTime(SerializableDateTime::fromString('2023-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01 00:00:00'))
                ->build(),
            []
        ));

        /** @var \App\Domain\Strava\Rewind\FindAvailableRewindYears\FindAvailableRewindYearsResponse $response */
        $response = $this->queryHandler->handle(
            new FindAvailableRewindYears(SerializableDateTime::fromString('2024-12-2500:00:00'))
        );

        $this->assertEquals(
            Years::fromArray([Year::fromInt(2025), Year::fromInt(2024), Year::fromInt(2023)]),
            $response->getAvailableRewindYears(),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindAvailableRewindYearsQueryHandler(
            $this->getConnection()
        );
    }
}
