<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

interface CombinedActivityStreamRepository
{
    public function add(CombinedActivityStream $combinedActivityStream): void;

    public function findOneForActivityAndUnitSystem(
        ActivityId $activityId,
        UnitSystem $unitSystem,
    ): CombinedActivityStream;

    public function findActivityIdsThatNeedStreamCombining(): ActivityIds;
}
