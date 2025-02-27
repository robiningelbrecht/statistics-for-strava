<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp\ImportFtp;

use App\Domain\Strava\Ftp\FtpRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

final readonly class ImportFtpCommandHandler implements CommandHandler
{
    public function __construct(
        private FtpValuesFromEnvFile $ftpValuesFromEnvFile,
        private FtpRepository $ftpRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportFtp);
        $command->getOutput()->writeln('Importing FTP...');

        $this->ftpRepository->removeAll();

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($this->ftpValuesFromEnvFile->getAll() as $ftp) {
            $this->ftpRepository->save($ftp);
            $command->getOutput()->writeln(sprintf(
                '  => Imported FTP set on %s (%s)...',
                $ftp->getSetOn()->format('d-m-Y'),
                $ftp->getFtp())
            );
        }
    }
}
