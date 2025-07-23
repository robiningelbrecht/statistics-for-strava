<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findLongestActivityFor(Years $years): Activity;

    public function count(): int;

    public function findAll(?int $limit = null): Activities;

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): Activities;

    public function findBySportTypes(SportTypes $sportTypes): Activities;

    public function hasForSportTypes(SportTypes $sportTypes): bool;

    public function delete(Activity $activity): void;

    public function findActivityIds(): ActivityIds;

    public function findActivityIdsThatNeedStreamImport(): ActivityIds;
}
