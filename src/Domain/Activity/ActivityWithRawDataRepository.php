<?php

declare(strict_types=1);

namespace App\Domain\Activity;

interface ActivityWithRawDataRepository
{
    public function find(ActivityId $activityId): ActivityWithRawData;

    public function exists(ActivityId $activityId): bool;

    public function add(ActivityWithRawData $activityWithRawData): void;

    public function update(ActivityWithRawData $activityWithRawData): void;

    public function delete(ActivityId $activityId): void;

    public function activityNeedsStreamImport(ActivityId $activityId): bool;

    public function markActivityStreamsAsImported(ActivityId $activityId): void;

    public function markActivitiesForDeletion(ActivityIds $activityIds): void;
}
