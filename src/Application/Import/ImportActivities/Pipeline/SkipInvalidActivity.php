<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Application\Import\ImportActivities\ActivitiesToSkipDuringImport;
use App\Application\Import\ImportActivities\ActivityVisibilitiesToImport;
use App\Application\Import\ImportActivities\SkipActivitiesRecordedBefore;
use App\Application\Import\ImportActivities\SkipActivityImport;
use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityVisibility;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypesToImport;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;

final readonly class SkipInvalidActivity implements ActivityImportStep
{
    public function __construct(
        private SportTypesToImport $sportTypesToImport,
        private ActivityVisibilitiesToImport $activityVisibilitiesToImport,
        private ActivitiesToSkipDuringImport $activitiesToSkipDuringImport,
        private ?SkipActivitiesRecordedBefore $skipActivitiesRecordedBefore,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $rawStravaData = $context->getRawStravaData();
        $sportType = SportType::from($rawStravaData['sport_type']);

        if (!$this->sportTypesToImport->has($sportType)) {
            throw new SkipActivityImport();
        }

        $activityVisibility = ActivityVisibility::from($rawStravaData['visibility']);
        if (!$this->activityVisibilitiesToImport->has($activityVisibility)) {
            throw new SkipActivityImport();
        }

        if ($this->skipActivitiesRecordedBefore?->isAfterOrOn(SerializableDateTime::createFromFormat(
            format: Activity::DATE_TIME_FORMAT,
            datetime: $rawStravaData['start_date_local'],
            timezone: SerializableTimezone::default(),
        ))) {
            throw new SkipActivityImport();
        }

        $activityId = ActivityId::fromUnprefixed((string) $rawStravaData['id']);
        if ($this->activitiesToSkipDuringImport->has($activityId)) {
            throw new SkipActivityImport();
        }

        return $context;
    }
}
