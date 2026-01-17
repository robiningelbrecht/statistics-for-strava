<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findAll(): Activities;

    public function findByStartDate(SerializableDateTime $startDate, ?ActivityType $activityType): Activities;

    public function findBySportTypes(SportTypes $sportTypes): Activities;

    public function findByActivityIds(ActivityIds $activityIds): Activities;

    /**
     * @return array<string, Activities>
     */
    public function findGroupedByActivityType(): array;
}
