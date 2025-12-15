<?php

declare(strict_types=1);

namespace App\Domain\Activity;

interface ActivityWithRawDataRepository
{
    public function find(ActivityId $activityId): ActivityWithRawData;

    public function exists(ActivityId $activityId): bool;

    public function add(ActivityWithRawData $activityWithRawData): void;

    public function update(ActivityWithRawData $activityWithRawData): void;

    public function markActivityStreamsAsImported(ActivityId $activityId): void;
}
