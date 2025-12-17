<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Stream\ActivityStreams;

final readonly class ActivityImportContext
{
    private function __construct(
        private ActivityId $activityId,
        /** @var array<string, mixed> */
        private array $rawStravaData,
        private bool $isNewActivity,
        private ?Activity $activity,
        private ActivityStreams $streams,
    ) {
    }

    /**
     * @param array<string, mixed> $rawStravaData
     */
    public static function create(
        ActivityId $activityId,
        array $rawStravaData,
        bool $isNewActivity,
    ): self {
        return new self(
            activityId: $activityId,
            rawStravaData: $rawStravaData,
            isNewActivity: $isNewActivity,
            activity: null,
            streams: ActivityStreams::empty(),
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function withActivity(Activity $activity): self
    {
        return clone ($this, [
            'activity' => $activity,
        ]);
    }

    public function withStreams(ActivityStreams $streams): self
    {
        return clone ($this, [
            'streams' => $streams,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawStravaData(): array
    {
        return $this->rawStravaData;
    }

    public function isNewActivity(): bool
    {
        return $this->isNewActivity;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function getStreams(): ActivityStreams
    {
        return $this->streams;
    }
}
