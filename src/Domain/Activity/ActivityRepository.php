<?php

namespace App\Domain\Activity;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findSummary(ActivityId $activityId): ActivitySummary;

    public function findAll(?int $limit = null): Activities;
}
