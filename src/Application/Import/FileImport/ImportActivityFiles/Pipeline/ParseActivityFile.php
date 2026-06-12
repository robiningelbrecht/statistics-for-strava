<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\ImportActivityFiles\Pipeline;

use App\Domain\Import\DuplicateActivityScanner;
use App\Domain\Import\FileParser\ActivityFileParsers;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Domain\Import\WatchDirectory;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final readonly class ParseActivityFile implements ImportActivityFileStep
{
    public function __construct(
        private WatchDirectory $watchDirectory,
        private ActivityFileParsers $activityFileParsers,
        private DuplicateActivityScanner $duplicateActivityScanner,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $file = RawActivityFile::from(
            filePath: $context->getFilePath(),
            fileContents: $this->watchDirectory->readFile($context->getFilePath())
        );

        $context = $context->withFile($file);

        if ($this->duplicateActivityScanner->isDuplicate($file)) {
            throw new SkipActivityFileImport();
        }

        $parsedFile = $this->activityFileParsers->parse($file);

        return $context
            ->withActivity($parsedFile->getActivity())
            ->withStreams($parsedFile->getStreams())
            ->withLaps($parsedFile->getLaps());
    }
}
