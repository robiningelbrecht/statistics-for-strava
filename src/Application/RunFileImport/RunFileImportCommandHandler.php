<?php

declare(strict_types=1);

namespace App\Application\RunFileImport;

use App\Application\Import\CalculateActivityMetrics\CalculateActivityMetrics;
use App\Application\Import\FileImport\ImportActivityFiles;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\FileSystem\PermissionChecker;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;

final readonly class RunFileImportCommandHandler implements CommandHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private PermissionChecker $fileSystemPermissionChecker,
        private Connection $connection,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof RunFileImport);

        $output = $command->getOutput();
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return;
        }

        $this->commandBus->dispatch(new ImportActivityFiles($output));
        $this->commandBus->dispatch(new CalculateActivityMetrics($output));

        $this->connection->executeStatement('VACUUM');
        $output->writeln('Database got vacuumed 🧹');
    }
}
