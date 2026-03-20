<?php

declare(strict_types=1);

namespace App\Domain\Activity\ImportHash;

use App\Domain\Activity\ActivityId;

interface ActivityImportHashRepository
{
    public function find(ActivityId $activityId): ?ActivityImportHash;

    public function save(ActivityImportHash $activityImportHash): void;
}
