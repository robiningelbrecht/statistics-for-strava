<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity\Lap;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Lap\ActivityLap;
use App\Domain\Strava\Activity\Lap\ActivityLapId;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final class ActivityLapBuilder
{
    private ActivityLapId $lapId;
    private ActivityId $activityId;
    private int $lapNumber;
    private string $name;
    private int $elapsedTimeInSeconds;
    private int $movingTimeInSeconds;
    private Meter $distance;
    private MetersPerSecond $averageSpeed;
    private MetersPerSecond $maxSpeed;
    private Meter $elevationDifference;
    private ?int $averageHeartRate;

    private function __construct()
    {
        $this->lapId = ActivityLapId::fromUnprefixed('test');
        $this->activityId = ActivityId::fromUnprefixed('test');
        $this->lapNumber = 1;
        $this->name = 'test';
        $this->distance = Meter::from(100);
        $this->elapsedTimeInSeconds = 120;
        $this->movingTimeInSeconds = 110;
        $this->elevationDifference = Meter::from(2);
        $this->averageSpeed = MetersPerSecond::from(3);
        $this->maxSpeed = MetersPerSecond::from(8);
        $this->averageHeartRate = null;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ActivityLap
    {
        return ActivityLap::fromState(
            lapId: $this->lapId,
            activityId: $this->activityId,
            lapNumber: $this->lapNumber,
            name: $this->name,
            elapsedTimeInSeconds: $this->elapsedTimeInSeconds,
            movingTimeInSeconds: $this->movingTimeInSeconds,
            distance: $this->distance,
            averageSpeed: $this->averageSpeed,
            maxSpeed: $this->maxSpeed,
            elevationDifference: $this->elevationDifference,
            averageHeartRate: $this->averageHeartRate,
        );
    }

    public function withLapId(ActivityLapId $lapId): self
    {
        $this->lapId = $lapId;

        return $this;
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withLapNumber(int $labNumber): self
    {
        $this->lapNumber = $labNumber;

        return $this;
    }

    public function withAverageSpeed(MetersPerSecond $averageSpeed): self
    {
        $this->averageSpeed = $averageSpeed;

        return $this;
    }

    public function withMaxSpeed(MetersPerSecond $maxSpeed): self
    {
        $this->maxSpeed = $maxSpeed;

        return $this;
    }
}
