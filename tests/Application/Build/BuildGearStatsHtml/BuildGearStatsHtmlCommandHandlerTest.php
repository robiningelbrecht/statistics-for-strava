<?php

namespace App\Tests\Application\Build\BuildGearStatsHtml;

use App\Application\Build\BuildGearStatsHtml\BuildGearStatsHtml;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class BuildGearStatsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('testy'))
                ->withGearId(GearId::fromUnprefixed('testy'))
                ->build(),
            rawData: []
        ));

        $this->commandBus->dispatch(new BuildGearStatsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
