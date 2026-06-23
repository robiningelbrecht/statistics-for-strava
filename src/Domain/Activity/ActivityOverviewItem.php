<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\Repository\Item;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ActivityOverviewItem implements Item
{
    private function __construct(
        private ActivityId $activityId,
        private ActivityName $name,
        private SportType $sportType,
        private SerializableDateTime $startDate,
        private Kilometer $distance,
    ) {
    }

    public static function fromState(
        ActivityId $activityId,
        ActivityName $name,
        SportType $sportType,
        SerializableDateTime $startDate,
        Kilometer $distance,
    ): self {
        return new self(
            activityId: $activityId,
            name: $name,
            sportType: $sportType,
            startDate: $startDate,
            distance: $distance,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): ActivityName
    {
        return $this->name;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDate;
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }
}
