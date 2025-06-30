<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;

interface ActivityStreamRepository
{
    public function add(ActivityStream $stream): void;

    public function update(ActivityStream $stream): void;

    public function delete(ActivityStream $stream): void;

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreams;

    public function findActivityIdsByStreamType(StreamType $streamType): ActivityIds;

    public function findOneByActivityAndStreamType(ActivityId $activityId, StreamType $streamType): ActivityStream;

    public function findByActivityId(ActivityId $activityId): ActivityStreams;

    public function findWithoutBestAverages(int $limit): ActivityStreams;

    public function findWithoutNormalizedPower(int $limit): ActivityStreams;
}
