<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\Maintenance\Task\IntervalUnit;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ProgressCalculationContext
{
    private function __construct(
        private GearIds $gearIds,
        private ActivityId $lastTaggedOnActivityId,
        private SerializableDateTime $lastTaggedOn,
        private IntervalUnit $intervalUnit,
        private int $intervalValue,
    ) {
    }

    public static function from(
        GearIds $gearIds,
        ActivityId $lastTaggedOnActivityId,
        SerializableDateTime $lastTaggedOn,
        IntervalUnit $intervalUnit,
        int $intervalValue,
    ): self {
        return new self(
            gearIds: $gearIds,
            lastTaggedOnActivityId: $lastTaggedOnActivityId,
            lastTaggedOn: $lastTaggedOn,
            intervalUnit: $intervalUnit,
            intervalValue: $intervalValue,
        );
    }

    public function getGearIds(): GearIds
    {
        return $this->gearIds;
    }

    public function getLastTaggedOnActivityId(): ActivityId
    {
        return $this->lastTaggedOnActivityId;
    }

    public function getLastTaggedOn(): SerializableDateTime
    {
        return $this->lastTaggedOn;
    }

    public function getIntervalUnit(): IntervalUnit
    {
        return $this->intervalUnit;
    }

    public function getIntervalValue(): int
    {
        return $this->intervalValue;
    }
}
