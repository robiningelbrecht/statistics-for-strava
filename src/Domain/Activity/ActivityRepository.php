<?php

namespace App\Domain\Activity;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;
}
