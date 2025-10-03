<?php

declare(strict_types=1);

namespace App\Domain\Activity\Split;

use App\Domain\Activity\ActivityId;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPer100Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\SecPerKm;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivitySplit_activityIdUnitSystemIndex', columns: ['activityId', 'unitSystem'])]
final class ActivitySplit implements SupportsAITooling
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
        $minAverageSpeed = $this->getMinAverageSpeed()->toFloat();
        $maxAverageSpeed = $this->getMaxAverageSpeed()->toFloat();

        if ($maxAverageSpeed <= 0.0) {
            return 0.0;
        }

        if ($maxAverageSpeed === $minAverageSpeed) {
            return 0.0;
        }

        $min = $minAverageSpeed * 0.85;
        $max = $maxAverageSpeed * 1.05;
        $averageSpeed = $this->getAverageSpeed()->toFloat();

        $relative = ($averageSpeed - $min) / ($max - $min) * 100;
        $relative = max(0, min(100, $relative));

        return round($relative, 2);
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

    public function getPaceInSecPer100Meter(): SecPer100Meter
    {
        return $this->getAverageSpeed()->toSecPerKm()->toSecPer100Meter();
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

    public function exportForAITooling(): array
    {
        return [
            'activityId' => $this->getActivityId()->toUnprefixedString(),
            'unitSystem' => $this->getUnitSystem()->value,
            'splitNumber' => $this->getSplitNumber(),
            'elapsedTimeInSeconds' => $this->getElapsedTimeInSeconds(),
            'movingTimeInSeconds' => $this->getMovingTimeInSeconds(),
            'distanceInMeter' => $this->getDistance(),
            'averageSpeedInMetersPerSecond' => $this->getAverageSpeed(),
            'elevationDifferenceInMeter' => $this->getElevationDifference(),
            'averageHeartRate' => $this->getAverageHeartRate(),
            'paceZone' => $this->getPaceZone(),
        ];
    }
}
