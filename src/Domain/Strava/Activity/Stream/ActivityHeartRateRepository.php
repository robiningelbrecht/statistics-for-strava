<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Athlete\HeartRateZone\TimeInHeartRateZones;

interface ActivityHeartRateRepository
{
    public function findTotalTimeInSecondsInHeartRateZones(): TimeInHeartRateZones;

    public function findTotalTimeInSecondsInHeartRateZonesForLast30Days(): TimeInHeartRateZones;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(ActivityId $activityId): array;
}
