<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Split;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Split\ActivitySplit;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final class ActivitySplitBuilder
{
    private ActivityId $activityId;
    private UnitSystem $unitSystem = UnitSystem::METRIC;
    private int $splitNumber = 1;
    private readonly Meter $distance;
    private readonly int $elapsedTimeInSeconds;
    private readonly int $movingTimeInSeconds;
    private readonly Meter $elevationDifference;
    private MetersPerSecond $averageSpeed;
    private MetersPerSecond $minAverageSpeed;
    private MetersPerSecond $maxAverageSpeed;
    private readonly int $paceZone;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('test');
        $this->distance = Meter::from(100);
        $this->elapsedTimeInSeconds = 120;
        $this->movingTimeInSeconds = 110;
        $this->elevationDifference = Meter::from(2);
        $this->averageSpeed = MetersPerSecond::from(3);
        $this->minAverageSpeed = MetersPerSecond::from(1);
        $this->maxAverageSpeed = MetersPerSecond::from(8);
        $this->paceZone = 0;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ActivitySplit
    {
        return ActivitySplit::fromState(
            activityId: $this->activityId,
            unitSystem: $this->unitSystem,
            splitNumber: $this->splitNumber,
            distance: $this->distance,
            elapsedTimeInSeconds: $this->elapsedTimeInSeconds,
            movingTimeInSeconds: $this->movingTimeInSeconds,
            elevationDifference: $this->elevationDifference,
            averageSpeed: $this->averageSpeed,
            minAverageSpeed: $this->minAverageSpeed,
            maxAverageSpeed: $this->maxAverageSpeed,
            paceZone: $this->paceZone
        );
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withUnitSystem(UnitSystem $unitSystem): self
    {
        $this->unitSystem = $unitSystem;

        return $this;
    }

    public function withSplitNumber(int $splitNumber): self
    {
        $this->splitNumber = $splitNumber;

        return $this;
    }

    public function withAverageSpeed(MetersPerSecond $averageSpeed): self
    {
        $this->averageSpeed = $averageSpeed;

        return $this;
    }

    public function withMinAverageSpeed(MetersPerSecond $minAverageSpeed): self
    {
        $this->minAverageSpeed = $minAverageSpeed;

        return $this;
    }

    public function withMaxAverageSpeed(MetersPerSecond $maxAverageSpeed): self
    {
        $this->maxAverageSpeed = $maxAverageSpeed;

        return $this;
    }
}
