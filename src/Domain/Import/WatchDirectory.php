<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Import\FileParser\RawActivityFile;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\Path;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

final readonly class WatchDirectory
{
    private const string FOLDER_NAME = 'watch';

    public function __construct(
        private KernelProjectDir $projectDir,
        private FilesystemOperator $defaultStorage,
    ) {
    }

    public function exists(): bool
    {
        return $this->defaultStorage->directoryExists(self::FOLDER_NAME);
    }

    public function listFiles(): DirectoryListing
    {
        return $this->defaultStorage->listContents(self::FOLDER_NAME, false)
            ->filter(fn (StorageAttributes $attributes): bool => $attributes->isFile());
    }

    public function readFile(Path $filePath): string
    {
        return $this->defaultStorage->read(self::FOLDER_NAME.'/'.$filePath->getFilename());
    }

    public function deleteFile(RawActivityFile $file): void
    {
        $this->defaultStorage->delete(self::FOLDER_NAME.'/'.$file->getPath()->getFilename());
    }

    public function getAbsolutePathFor(StorageAttributes $file): Path
    {
        return Path::fromString($this->projectDir.'/'.$file->path());
    }
}
