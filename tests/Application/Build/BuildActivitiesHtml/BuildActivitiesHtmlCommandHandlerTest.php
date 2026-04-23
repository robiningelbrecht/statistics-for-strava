<?php

namespace App\Tests\Application\Build\BuildActivitiesHtml;

use App\Application\Build\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\Split\ActivitySplitBuilder;

class BuildActivitiesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
        $this->assertFileSystemWrites(
            fileSystem: $this->getContainer()->get('api.storage'),
            contentIsCompressed: true
        );
    }

    public function testHandleRendersGapInRunSplitsWhenGapPaceExists(): void
    {
        $this->provideFullTestSet();

        $activityId = ActivityId::fromUnprefixed('gap-run');
        $this->addRunActivity($activityId);
        $this->addRunSplitWithGap($activityId, 1, 1000, 300);
        $this->addRunSplitWithGap($activityId, 2, 1000, 310);

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        $html = $this->getContainer()->get('build.storage')->read('activity/'.$activityId.'.html');

        $this->assertStringContainsString('<dd class="text-xs text-gray-500">GAP</dd>', $html);
        $this->assertStringContainsString('>5:00<', $html);
        $this->assertStringContainsString('>5:10<', $html);
    }

    public function testHandleDoesNotRenderGapColumnWhenRunSplitsHaveNoGapPace(): void
    {
        $this->provideFullTestSet();

        $activityId = ActivityId::fromUnprefixed('no-gap-run');
        $this->addRunActivity($activityId);
        $this->getContainer()->get(ActivitySplitRepository::class)->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSplitNumber(1)
                ->withDistanceInMeter(1000)
                ->build()
        );

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        $html = $this->getContainer()->get('build.storage')->read('activity/'.$activityId.'.html');

        $this->assertStringNotContainsString('<dd class="text-xs text-gray-500">GAP</dd>', $html);
        $this->assertStringNotContainsString('<div class="w-20 px-2 py-2">GAP</div>', $html);
    }

    private function addRunActivity(ActivityId $activityId): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSportType(SportType::RUN)
                ->withAverageSpeed(KmPerHour::from(12))
                ->withMovingTimeInSeconds(1800)
                ->build(),
            [],
        ));
    }

    private function addRunSplitWithGap(ActivityId $activityId, int $splitNumber, int $distanceInMeters, int $gapPaceInSecondsPerKm): void
    {
        $this->getContainer()->get(ActivitySplitRepository::class)->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId($activityId)
                ->withSplitNumber($splitNumber)
                ->withDistanceInMeter($distanceInMeters)
                ->withGapPace(SecPerKm::from($gapPaceInSecondsPerKm))
                ->build()
        );
    }
}
