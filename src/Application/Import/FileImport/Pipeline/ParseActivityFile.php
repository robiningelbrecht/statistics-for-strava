<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\Pipeline;

use App\Domain\Import\FileImportRepository;
use App\Domain\Import\FileParser\ActivityFileParsers;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Domain\Import\WatchDirectory;

final readonly class ParseActivityFile implements ImportActivityFileStep
{
    public function __construct(
        private WatchDirectory $watchDirectory,
        private ActivityFileParsers $activityFileParsers,
        private FileImportRepository $fileImportRepository,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $file = RawActivityFile::from(
            filePath: $context->getFilePath(),
            fileContents: $this->watchDirectory->readFile($context->getFilePath())
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
