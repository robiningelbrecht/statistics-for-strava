<?php

declare(strict_types=1);

namespace App\Domain\Import\UploadActivityFile;

use App\Domain\Import\ImportMode;
use App\Domain\Import\WatchDirectory;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class UploadActivityFileCommandHandler implements CommandHandler
{
    public function __construct(
        private ImportMode $importMode,
        private WatchDirectory $watchDirectory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UploadActivityFile);

        if (!$this->importMode->isFiles()) {
            throw CannotUploadActivityFile::importModeIsNotFiles();
        }

        $this->watchDirectory->writeFile($command->getFilename(), $command->getContents());
    }
}
