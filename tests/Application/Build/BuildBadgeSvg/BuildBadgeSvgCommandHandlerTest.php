<?php

namespace App\Tests\Application\Build\BuildBadgeSvg;

use App\Application\Build\BuildBadgeSvg\BuildBadgeSvg;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class BuildBadgeSvgCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $activity = ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withName('🕍➡️⛱️➡️🚜 Climb Portal: Côte de la Redoute')
                ->withStartDateTime(SerializableDateTime::fromString('2025-05-17'))
                ->build(),
            rawData: []
        );
        $this->getContainer()->get(ActivityRepository::class)->add($activity);

        $this->commandBus->dispatch(new BuildBadgeSvg(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        $fileSystems = [
            $this->getContainer()->get('build.storage'),
            $this->getContainer()->get('file.storage'),
        ];

        foreach ($fileSystems as $fileSystem) {
            $this->assertFileSystemWrites($fileSystem);
        }
    }
}
