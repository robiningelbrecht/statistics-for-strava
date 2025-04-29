<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\ValueObject\Collection;

final class ActivitiesToSkipDuringImport extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityId::class;
    }

    /**
     * @param string[] $activityIds
     */
    public static function from(array $activityIds): self
    {
        if (0 === count($activityIds)) {
            return self::empty();
        }

        return self::fromArray(array_map(
            fn (string $activityId) => ActivityId::fromUnprefixed($activityId),
            $activityIds
        ));
    }
}
