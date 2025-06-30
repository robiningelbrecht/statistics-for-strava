<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentId;

interface SegmentEffortRepository
{
    public function add(SegmentEffort $segmentEffort): void;

    public function deleteForActivity(ActivityId $activityId): void;

    public function find(SegmentEffortId $segmentEffortId): SegmentEffort;

    public function findTopXBySegmentId(SegmentId $segmentId, int $limit): SegmentEfforts;

    public function findHistoryBySegmentId(SegmentId $segmentId): SegmentEfforts;

    public function countBySegmentId(SegmentId $segmentId): int;

    public function findByActivityId(ActivityId $activityId): SegmentEfforts;
}
