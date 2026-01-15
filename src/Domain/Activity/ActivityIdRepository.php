<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Gear\GearIds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;

interface ActivityIdRepository
{
    public function count(): int;

    public function findAll(): ActivityIds;

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): ActivityIds;

    public function findBySportTypes(SportTypes $sportTypes): ActivityIds;

    public function hasForSportTypes(SportTypes $sportTypes): bool;

    public function findUniqueStravaGearIds(?ActivityIds $restrictToActivityIds): GearIds;

    public function findActivityIdsMarkedForDeletion(): ActivityIds;

    public function findLongestFor(Years $years): ActivityId;
}
