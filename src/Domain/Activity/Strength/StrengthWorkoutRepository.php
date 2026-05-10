<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use App\Domain\Activity\ActivityId;

interface StrengthWorkoutRepository
{
    public function saveForActivity(ActivityId $activityId, StrengthWorkoutExercises $exercises): void;

    public function findByActivityId(ActivityId $activityId): StrengthWorkoutExercises;

    public function isImportedForActivity(ActivityId $activityId): bool;

    public function deleteForActivity(ActivityId $activityId): void;
}
