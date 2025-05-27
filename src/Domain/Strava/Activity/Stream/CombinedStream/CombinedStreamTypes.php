<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Collection;

final class CombinedStreamTypes extends Collection
{
    public function getItemClassName(): string
    {
        return CombinedStreamType::class;
    }

    public static function othersFor(ActivityType $activityType): self
    {
        if (ActivityType::RIDE === $activityType) {
            return self::fromArray([
                CombinedStreamType::ALTITUDE,
                CombinedStreamType::HEART_RATE,
                CombinedStreamType::WATTS,
                CombinedStreamType::CADENCE,
            ]);
        }

        return self::fromArray([
            CombinedStreamType::ALTITUDE,
            CombinedStreamType::HEART_RATE,
            CombinedStreamType::PACE,
        ]);
    }
}
