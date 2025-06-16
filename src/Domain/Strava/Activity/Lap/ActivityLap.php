<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Lap;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivitySplit_activityId', columns: ['activityId'])]
final readonly class ActivityLap
{
    use ProvideTimeFormats;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'integer')]
        private ActivityLapId $lapId,
        #[ORM\Column(type: 'string')]
        private ActivityId $activityId,
        #[ORM\Column(type: 'integer')]
        private int $lapNumber,
        #[ORM\Column(type: 'string')]
        private string $name,
        #[ORM\Column(type: 'integer')]
        private int $elapsedTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private Meter $distance,
        #[ORM\Column(type: 'float')]
        private MetersPerSecond $averageSpeed,
        #[ORM\Column(type: 'float')]
        private MetersPerSecond $maxSpeed,
        #[ORM\Column(type: 'integer')]
        private Meter $elevationDifference,
        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $averageHeartRate,
    ) {
    }

    public static function create(
        ActivityLapId $lapId,
        ActivityId $activityId,
        int $lapNumber,
        string $name,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $distance,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $maxSpeed,
        Meter $elevationDifference,
        ?int $averageHeartRate,
    ): self {
        return new self(
            lapId: $lapId,
            activityId: $activityId,
            lapNumber: $lapNumber,
            name: $name,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            distance: $distance,
            averageSpeed: $averageSpeed,
            maxSpeed: $maxSpeed,
            elevationDifference: $elevationDifference,
            averageHeartRate: $averageHeartRate,
        );
    }

    public static function fromState(
        ActivityLapId $lapId,
        ActivityId $activityId,
        int $lapNumber,
        string $name,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $distance,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $maxSpeed,
        Meter $elevationDifference,
        ?int $averageHeartRate,
    ): self {
        return new self(
            lapId: $lapId,
            activityId: $activityId,
            lapNumber: $lapNumber,
            name: $name,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            distance: $distance,
            averageSpeed: $averageSpeed,
            maxSpeed: $maxSpeed,
            elevationDifference: $elevationDifference,
            averageHeartRate: $averageHeartRate,
        );
    }

    public function getLapId(): ActivityLapId
    {
        return $this->lapId;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getLapNumber(): int
    {
        return $this->lapNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getElapsedTimeInSeconds(): int
    {
        return $this->elapsedTimeInSeconds;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getDistance(): Meter
    {
        return $this->distance;
    }

    public function getAverageSpeed(): MetersPerSecond
    {
        return $this->averageSpeed;
    }

    public function getMaxSpeed(): MetersPerSecond
    {
        return $this->maxSpeed;
    }

    public function getElevationDifference(): Meter
    {
        return $this->elevationDifference;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }
}
