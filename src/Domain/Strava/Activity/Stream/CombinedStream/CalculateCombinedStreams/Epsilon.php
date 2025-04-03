<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class Epsilon
{
    private float $value;

    public function __construct(
        Meter $totalDistance,
        Meter $elevationVariance,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $speedVariance,
        ActivityType $activityType,
    ) {
        $baseEpsilon = match ($activityType) {
            ActivityType::RUN, ActivityType::WALK => 0.5,
            default => 1.0,
        };

        $velocityThreshold = max(0.5, $averageSpeed->toFloat() * 0.2);

        $distanceScaling = match ($activityType) {
            ActivityType::RUN, ActivityType::WALK => 3000,
            ActivityType::RIDE => 8000,
            default => 5000,
        };

        $velocityPenalty = match ($activityType) {
            ActivityType::RUN, ActivityType::WALK => 0.2,
            default => 0.5,
        };

        $this->value = min(2.5, max(0.4,
            $baseEpsilon +
            ($totalDistance->toFloat() / $distanceScaling) +
            ($elevationVariance->toFloat() / 1000) -
            ($speedVariance->toFloat() > $velocityThreshold ? $velocityPenalty : 0)
        ));
    }

    public static function create(
        Meter $totalDistance,
        Meter $elevationVariance,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $speedVariance,
        ActivityType $activityType,
    ): self {
        return new self(
            totalDistance: $totalDistance,
            elevationVariance: $elevationVariance,
            averageSpeed: $averageSpeed,
            speedVariance: $speedVariance,
            activityType: $activityType
        );
    }

    public function toFloat(): float
    {
        return $this->value;
    }
}
