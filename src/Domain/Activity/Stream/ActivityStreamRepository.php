<?php

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;

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

    public function findWithoutDistributionValues(int $limit): ActivityStreams;
}
