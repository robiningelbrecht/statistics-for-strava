<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Activity\ImportSource;
use App\Domain\Import\FileParser\RawActivityFile;
use Doctrine\DBAL\Connection;

final readonly class DuplicateActivityScanner
{
    public function __construct(
        private Connection $connection,
        private FileImportRepository $fileImportRepository,
    ) {
    }

    public function isDuplicate(RawActivityFile $file): bool
    {
        // Same file content already imported (file -> file).
        if ($this->fileImportRepository->existsForFileHash($file->getHash())) {
            return true;
        }

        // Same activity already imported from Strava (strava -> file),
        // matched on the uploaded file's name.
        return $this->existsStravaActivityForFilename($file->getPath()->getFilename());
    }

    private function existsStravaActivityForFilename(string $filename): bool
    {
        $count = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('Activity')
            ->andWhere('externalReferenceId = :externalReferenceId')
            ->andWhere('importSource = :importSource')
            ->setParameter('externalReferenceId', $filename)
            ->setParameter('importSource', ImportSource::STRAVA_API->value)
            ->executeQuery()
            ->fetchOne();

        return (int) $count > 0;
    }
}
