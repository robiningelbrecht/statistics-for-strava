<?php

namespace App\Tests\Application\Import\ImportActivities\Pipeline;

use App\Application\Import\ImportActivities\ActivitiesToSkipDuringImport;
use App\Application\Import\ImportActivities\ActivityVisibilitiesToImport;
use App\Application\Import\ImportActivities\Pipeline\ActivityImportContext;
use App\Application\Import\ImportActivities\Pipeline\SkipInvalidActivity;
use App\Application\Import\ImportActivities\SkipActivityImport;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportTypesToImport;
use PHPUnit\Framework\TestCase;

class SkipInvalidActivityTest extends TestCase
{
    public function testProcessWhenSportTypeIsNotIncluded(): void
    {
        $skipInvalidActivity = new SkipInvalidActivity(
            sportTypesToImport: SportTypesToImport::from(['Ride']),
            activityVisibilitiesToImport: ActivityVisibilitiesToImport::from([]),
            activitiesToSkipDuringImport: ActivitiesToSkipDuringImport::from([]),
            skipActivitiesRecordedBefore: null,
        );

        $context = ActivityImportContext::create(
            activityId: ActivityId::fromUnprefixed(1),
            rawStravaData: ['sport_type' => 'Run']
        );

        $this->expectExceptionObject(new SkipActivityImport());

        $skipInvalidActivity->process($context);
    }
}
