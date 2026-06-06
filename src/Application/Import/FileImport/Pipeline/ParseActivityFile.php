<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\Pipeline;

use App\Domain\Import\FileImportRepository;
use App\Domain\Import\FileParser\ActivityFileParsers;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Infrastructure\ValueObject\String\Path;
use League\Flysystem\FilesystemOperator;

final readonly class ParseActivityFile implements ImportActivityFileStep
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private ActivityFileParsers $activityFileParsers,
        private FileImportRepository $fileImportRepository,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $relativePath = (string) $context->getFilePath();

        // The parsers need a real, readable path on the local filesystem.
        $file = RawActivityFile::from(
            filePath: Path::fromString(sprintf('%s/%s', rtrim($this->activityFileStorageDirectory, '/'), $relativePath)),
            fileContents: $this->fileStorage->read($relativePath)
        );

        $context = $context->withFile($file);

        if ($this->fileImportRepository->existsForFileHash($file->getHash())) {
            throw new SkipActivityFileImport();
        }

        $parsedFile = $this->activityFileParsers->parse($file);

        return $context
            ->withActivity($parsedFile->getActivity())
            ->withStreams($parsedFile->getStreams())
            ->withLaps($parsedFile->getLaps());
    }
}
