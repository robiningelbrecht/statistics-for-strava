<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Gear\GearIds;
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

    public function findUniqueStravaGearIds(?ActivityIds $restrictToActivityIds): GearIds;

    public function findActivityIdsThatNeedStreamImport(): ActivityIds;
}
