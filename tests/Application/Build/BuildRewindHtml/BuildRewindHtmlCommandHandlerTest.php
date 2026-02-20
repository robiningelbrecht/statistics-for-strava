<?php

namespace App\Tests\Application\Build\BuildRewindHtml;

use App\Application\Build\BuildRewindHtml\BuildRewindHtml;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\ProvideTestData;

class BuildRewindHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    use ProvideTestData;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->withStartDateTime(SerializableDateTime::fromString('2021-03-01'))
                ->build(),
            []
        ));

        $this->commandBus->dispatch(new BuildRewindHtml(SerializableDateTime::fromString('2025-10-01T00:00:00+00:00')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }

    public function testHandleWhenNoRewindsToCompare(): void
    {
        /** @var KeyValueStore $keyValueStore */
        $keyValueStore = $this->getContainer()->get(KeyValueStore::class);
        $keyValueStore->save(KeyValue::fromState(
            Key::THEME,
            Value::fromString(Json::encode($this->buildThemeConfig())),
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->withStartDateTime(SerializableDateTime::fromString('2023-03-01'))
                ->build(),
            []
        ));

        $this->commandBus->dispatch(new BuildRewindHtml(SerializableDateTime::fromString('2023-10-01T00:00:00+00:00')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
