<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Athlete\HeartRateZone\TimeInHeartRateZones;

interface ActivityHeartRateRepository
{
    public function findTotalTimeInSecondsInHeartRateZones(): TimeInHeartRateZones;

    public function findTotalTimeInSecondsInHeartRateZonesForLast30Days(): TimeInHeartRateZones;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(ActivityId $activityId): array;
}
