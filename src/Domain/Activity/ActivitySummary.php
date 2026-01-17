<?php

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ActivitySummary
{
    private function __construct(
        private ActivityId $activityId,
        private string $name,
        private SerializableDateTime $startDateTime,
    ) {
    }

    public static function create(
        ActivityId $activityId,
        string $name,
        SerializableDateTime $startDateTime,
    ): self {
        return new self(
            activityId: $activityId,
            name: $name,
            startDateTime: $startDateTime,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDateTime;
    }
}
