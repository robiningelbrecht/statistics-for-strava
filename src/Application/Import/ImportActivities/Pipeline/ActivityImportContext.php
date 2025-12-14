<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;

final readonly class ActivityImportContext
{
    private function __construct(
        private ActivityId $activityId,
        /** @var array<string, mixed>|null */
        private ?array $rawStravaData,
        private ?bool $isNewActivity,
        private ?Activity $activity,
    ) {
    }

    /**
     * @param array<string, mixed>|null $rawStravaData
     */
    public static function create(
        ActivityId $activityId,
        ?array $rawStravaData,
    ): self {
        return new self(
            activityId: $activityId,
            rawStravaData: $rawStravaData,
            isNewActivity: null,
            activity: null,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function withIsNewActivity(bool $isNewActivity): self
    {
        return clone ($this, [
            'isNewActivity' => $isNewActivity,
        ]);
    }

    public function withActivity(Activity $activity): self
    {
        return clone ($this, [
            'activity' => $activity,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawStravaData(): array
    {
        return $this->rawStravaData ?? throw new RawStravaDataNotSet();
    }

    public function isNewActivity(): ?bool
    {
        return $this->isNewActivity;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }
}
