<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Activity\ActivityId;

interface FileImportRepository
{
    public function add(FileImport $fileImport): void;

    public function existsForFileHash(string $fileHash): bool;

    public function deleteForActivity(ActivityId $activityId): void;
}
