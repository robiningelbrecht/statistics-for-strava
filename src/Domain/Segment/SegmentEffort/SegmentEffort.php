<?php

declare(strict_types=1);

namespace App\Domain\Segment\SegmentEffort;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Domain\Segment\SegmentId;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'SegmentEffort_segmentIndex', columns: ['segmentId'])]
#[ORM\Index(name: 'SegmentEffort_activityIndex', columns: ['activityId'])]
final class SegmentEffort implements SupportsAITooling
{
    use ProvideTimeFormats;

    private ?Activity $activity = null;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly SegmentEffortId $segmentEffortId,
        #[ORM\Column(type: 'string')]
        private readonly SegmentId $segmentId,
        #[ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'string')]
        private readonly string $name,
        #[ORM\Column(type: 'float')]
        private readonly float $elapsedTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private readonly Kilometer $distance,
        #[ORM\Column(type: 'float', nullable: true)]
        private readonly ?float $averageWatts,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxHeartRate,
        private readonly ?int $rank,
    ) {
    }

    public static function create(
        SegmentEffortId $segmentEffortId,
        SegmentId $segmentId,
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        string $name,
        float $elapsedTimeInSeconds,
        Kilometer $distance,
        ?float $averageWatts,
        ?int $averageHeartRate,
        ?int $maxHeartRate,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            name: $name,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            distance: $distance,
            averageWatts: $averageWatts,
            averageHeartRate: $averageHeartRate,
            maxHeartRate: $maxHeartRate,
            rank: null,
        );
    }

    public static function fromState(
        SegmentEffortId $segmentEffortId,
        SegmentId $segmentId,
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        string $name,
        float $elapsedTimeInSeconds,
        Kilometer $distance,
        ?float $averageWatts,
        ?int $averageHeartRate,
        ?int $maxHeartRate,
        ?int $rank,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            name: $name,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            distance: $distance,
            averageWatts: $averageWatts,
            averageHeartRate: $averageHeartRate,
            maxHeartRate: $maxHeartRate,
            rank: $rank,
        );
    }

    public function getId(): SegmentEffortId
    {
        return $this->segmentEffortId;
    }

    public function getSegmentId(): SegmentId
    {
        return $this->segmentId;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDateTime(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getElapsedTimeInSeconds(): float
    {
        return $this->elapsedTimeInSeconds;
    }

    public function getElapsedTimeFormatted(): string
    {
        return $this->formatDurationForHumans((int) round($this->getElapsedTimeInSeconds()));
    }

    public function getAverageWatts(): ?float
    {
        return $this->averageWatts;
    }

    public function getAverageSpeed(): KmPerHour
    {
        if ($this->getElapsedTimeInSeconds() <= 0) {
            return KmPerHour::zero();
        }
        $averageSpeed = $this->getDistance()->toMeter()->toFloat() / $this->getElapsedTimeInSeconds();

        return MetersPerSecond::from($averageSpeed)->toKmPerHour();
    }

    public function getPaceInSecPerKm(): SecPerKm
    {
        return $this->getAverageSpeed()->toMetersPerSecond()->toSecPerKm();
    }

    public function getPaceInSecPer100Meter(): SecPer100Meter
    {
        return $this->getAverageSpeed()->toMetersPerSecond()->toSecPerKm()->toSecPer100Meter();
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }

    public function getMaxHeartRate(): ?int
    {
        return $this->maxHeartRate;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function enrichWithActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportForAITooling(): array
    {
        return [
            'id' => $this->getId()->toUnprefixedString(),
            'segmentId' => $this->getSegmentId()->toUnprefixedString(),
            'activityId' => $this->getActivityId()->toUnprefixedString(),
            'startDateTime' => $this->getStartDateTime()->format('Y-m-d'),
            'name' => $this->getName(),
            'distanceInKilometer' => $this->getDistance(),
            'averageWatts' => $this->getAverageWatts(),
            'averageHeatRate' => $this->getAverageHeartRate(),
            'maxHeartRate' => $this->getMaxHeartRate(),
            'rank' => $this->getRank(),
        ];
    }
}
