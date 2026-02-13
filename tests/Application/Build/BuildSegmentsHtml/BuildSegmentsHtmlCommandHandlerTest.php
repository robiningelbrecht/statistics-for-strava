<?php

namespace App\Tests\Application\Build\BuildSegmentsHtml;

use App\Application\Build\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Segment\SegmentBuilder;
use App\Tests\Domain\Segment\SegmentEffort\SegmentEffortBuilder;

class BuildSegmentsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed('10'))
                ->withName(Name::fromString('Segment Ten'))
                ->withDistance(Kilometer::from(0.1))
                ->withMaxGradient(5.3)
                ->withIsFavourite(true)
                ->withDeviceName('MyWhoosh')
                ->withSportType(SportType::VIRTUAL_RIDE)
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed('11'))
                ->withSegmentId(SegmentId::fromUnprefixed('10'))
                ->withActivityId(ActivityId::fromUnprefixed('9542782314'))
                ->withElapsedTimeInSeconds(10.3)
                ->withAverageWatts(200)
                ->withDistance(Kilometer::from(0.1))
                ->withName('An effort')
                ->build()
        );

        $this->commandBus->dispatch(new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
        $this->assertFileSystemWrites(
            fileSystem: $this->getContainer()->get('api.storage'),
            contentIsCompressed: true
        );
    }
}
