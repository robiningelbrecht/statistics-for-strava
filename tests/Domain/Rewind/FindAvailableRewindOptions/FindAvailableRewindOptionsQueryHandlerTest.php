<?php

namespace App\Tests\Domain\Rewind\FindAvailableRewindOptions;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Rewind\FindAvailableRewindOptions\FindAvailableRewindOptions;
use App\Domain\Rewind\FindAvailableRewindOptions\FindAvailableRewindOptionsQueryHandler;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class FindAvailableRewindOptionsQueryHandlerTest extends ContainerTestCase
{
    private FindAvailableRewindOptionsQueryHandler $queryHandler;

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

        /** @var \App\Domain\Rewind\FindAvailableRewindOptions\FindAvailableRewindOptionsResponse $response */
        $response = $this->queryHandler->handle(
            new FindAvailableRewindOptions(SerializableDateTime::fromString('2025-01-01 00:00:00'))
        );

        $this->assertEquals(
            [
                'all-time',
                2024,
                2023,
            ],
            $response->getAvailableOptions(),
        );
    }

    public function testHandleWhenAfterCutOffDate(): void
    {
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

        /** @var \App\Domain\Rewind\FindAvailableRewindOptions\FindAvailableRewindOptionsResponse $response */
        $response = $this->queryHandler->handle(
            new FindAvailableRewindOptions(SerializableDateTime::fromString('2024-12-2500:00:00'))
        );

        $this->assertEquals(
            [
                'all-time',
                2024,
                2023,
            ],
            $response->getAvailableOptions(),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryHandler = new FindAvailableRewindOptionsQueryHandler(
            $this->getConnection()
        );
    }
}
