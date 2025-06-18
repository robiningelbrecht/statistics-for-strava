<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;

final readonly class Epsilon
{
    private float $value;

    public function __construct(
        ActivityType $activityType,
    ) {
        $this->value = match ($activityType) {
            ActivityType::RUN, ActivityType::WALK => 0.5,
            default => 1.0,
        };
    }

    public static function create(
        ActivityType $activityType,
    ): self {
        return new self($activityType);
    }

    public function toFloat(): float
    {
        return $this->value;
    }
}
