<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\Year;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findLongestActivityForYear(Year $year): Activity;

    public function count(): int;

    public function findAll(?int $limit = null): Activities;

    public function delete(Activity $activity): void;

    public function findActivityIds(): ActivityIds;

    public function findActivityIdsThatNeedStreamImport(): ActivityIds;
}
