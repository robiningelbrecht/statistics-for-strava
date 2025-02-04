<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivitySplit_activityIdUnitSystemIndex', columns: ['activityId', 'unitSystem'])]
final class ActivitySplit
{
    use ProvideTimeFormats;

    private ?int $averageHeartRate = null;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly UnitSystem $unitSystem,
        #[ORM\Id, ORM\Column(type: 'integer')]
        private readonly int $splitNumber,
        #[ORM\Column(type: 'integer')]
        private readonly Meter $distance,
        #[ORM\Column(type: 'integer')]
        private readonly int $elapsedTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private readonly int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private readonly Meter $elevationDifference,
        #[ORM\Column(type: 'float')]
        private readonly MetersPerSecond $averageSpeed,
        #[ORM\Column(type: 'float')]
        private readonly MetersPerSecond $minAverageSpeed,
        #[ORM\Column(type: 'integer')]
        private readonly MetersPerSecond $maxAverageSpeed,
        #[ORM\Column(type: 'integer')]
        private readonly int $paceZone,
    ) {
    }

    public static function create(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        int $splitNumber,
        Meter $distance,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $elevationDifference,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $minAverageSpeed,
        MetersPerSecond $maxAverageSpeed,
        int $paceZone,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            splitNumber: $splitNumber,
            distance: $distance,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            elevationDifference: $elevationDifference,
            averageSpeed: $averageSpeed,
            minAverageSpeed: $minAverageSpeed,
            maxAverageSpeed: $maxAverageSpeed,
            paceZone: $paceZone,
        );
    }

    public static function fromState(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        int $splitNumber,
        Meter $distance,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $elevationDifference,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $minAverageSpeed,
        MetersPerSecond $maxAverageSpeed,
        int $paceZone,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            splitNumber: $splitNumber,
            distance: $distance,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            elevationDifference: $elevationDifference,
            averageSpeed: $averageSpeed,
            minAverageSpeed: $minAverageSpeed,
            maxAverageSpeed: $maxAverageSpeed,
            paceZone: $paceZone,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getUnitSystem(): UnitSystem
    {
        return $this->unitSystem;
    }

    public function getSplitNumber(): int
    {
        return $this->splitNumber;
    }

    public function getDistance(): Meter
    {
        return $this->distance;
    }

    public function getElapsedTimeInSeconds(): int
    {
        return $this->elapsedTimeInSeconds;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getElevationDifference(): Meter
    {
        return $this->elevationDifference;
    }

    public function getAverageSpeed(): MetersPerSecond
    {
        return $this->averageSpeed;
    }

    public function getRelativePacePercentage(): float
    {
        if (0.0 === $this->getMaxAverageSpeed()->toFloat()) {
            return 0;
        }
        if (0.0 === $this->getMaxAverageSpeed()->toFloat() - $this->getMinAverageSpeed()->toFloat()) {
            return 0;
        }

        $adjustMinSpeedPercentage = 0.85;
        $adjustMaxSpeedPercentage = 1.05;

        $maxAverageSpeed = MetersPerSecond::from($this->getMaxAverageSpeed()->toFloat() * $adjustMaxSpeedPercentage);
        $minAverageSpeed = MetersPerSecond::from($this->getMinAverageSpeed()->toFloat() * $adjustMinSpeedPercentage);

        $step = round(100 / ($maxAverageSpeed->toFloat() - $minAverageSpeed->toFloat()), 2);
        // Relative = step *  (value - min).
        $relativePercentage = $step * ($this->getAverageSpeed()->toFloat() - $minAverageSpeed->toFloat());

        return round($relativePercentage, 2);
    }

    public function getMinAverageSpeed(): MetersPerSecond
    {
        return $this->minAverageSpeed;
    }

    public function getMaxAverageSpeed(): MetersPerSecond
    {
        return $this->maxAverageSpeed;
    }

    public function getPaceInSecPerKm(): SecPerKm
    {
        return $this->getAverageSpeed()->toSecPerKm();
    }

    public function getPaceZone(): int
    {
        return $this->paceZone;
    }

    public function enrichWithAverageHeartRate(int $averageHeartRate): void
    {
        $this->averageHeartRate = $averageHeartRate;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }
}
