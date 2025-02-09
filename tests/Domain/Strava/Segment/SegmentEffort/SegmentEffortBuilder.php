<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class SegmentEffortBuilder
{
    private SegmentEffortId $segmentEffortId;
    private SegmentId $segmentId;
    private ActivityId $activityId;
    private SerializableDateTime $startDateTime;
    private string $name;
    private float $elapsedTimeInSeconds;
    private Kilometer $distance;
    private ?float $averageWatts;
    private ?int $rank;

    private function __construct()
    {
        $this->segmentEffortId = SegmentEffortId::fromUnprefixed('1');
        $this->segmentId = SegmentId::fromUnprefixed('1');
        $this->activityId = ActivityId::fromUnprefixed('1');
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->name = 'Segment One';
        $this->elapsedTimeInSeconds = 9.3;
        $this->distance = Kilometer::from(0.1);
        $this->averageWatts = 200;
        $this->rank = 1;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): SegmentEffort
    {
        return SegmentEffort::fromState(
            segmentEffortId: $this->segmentEffortId,
            segmentId: $this->segmentId,
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            name: $this->name,
            elapsedTimeInSeconds: $this->elapsedTimeInSeconds,
            distance: $this->distance,
            averageWatts: $this->averageWatts,
            rank: $this->rank,
        );
    }

    public function withSegmentEffortId(SegmentEffortId $id): self
    {
        $this->segmentEffortId = $id;

        return $this;
    }

    public function withSegmentId(SegmentId $id): self
    {
        $this->segmentId = $id;

        return $this;
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withElapsedTimeInSeconds(float $seconds): self
    {
        $this->elapsedTimeInSeconds = $seconds;

        return $this;
    }

    public function withAverageWatts(float $watts): self
    {
        $this->averageWatts = $watts;

        return $this;
    }

    public function withDistance(Kilometer $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withRank(?int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }
}
