<?php

declare(strict_types=1);

namespace App\Domain\Activity;

interface ActivitySummaryRepository
{
    public function find(ActivityId $activityId): ActivitySummary;
}
