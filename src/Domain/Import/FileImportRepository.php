<?php

declare(strict_types=1);

namespace App\Domain\Import;

interface FileImportRepository
{
    public function add(FileImport $fileImport): void;

    public function existsForFileHash(string $fileHash): bool;

    public function findAll(): FileImports;
}
