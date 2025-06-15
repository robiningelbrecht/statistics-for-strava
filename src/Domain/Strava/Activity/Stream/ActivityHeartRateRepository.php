<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;

interface ActivityHeartRateRepository
{
    public function findTotalTimeInSecondsInHeartRateZone(string $heartRateZoneName): int;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(ActivityId $activityId): array;
}
