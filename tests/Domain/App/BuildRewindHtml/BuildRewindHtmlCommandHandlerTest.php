<?php

namespace App\Tests\Domain\App\BuildRewindHtml;

use App\Domain\App\BuildRewindHtml\BuildRewindHtml;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class BuildRewindHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
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
}
