<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\String\CompressedString;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalFileImportRepository extends DbalRepository implements FileImportRepository
{
    public function add(FileImport $fileImport): void
    {
        $sql = 'INSERT INTO FileImport (fileImportId, originalFilename, fileHash, fileContents, source, status, errorMessage, activityId, importedOn)
        VALUES (:fileImportId, :originalFilename, :fileHash, :fileContents, :source, :status, :errorMessage, :activityId, :importedOn)';

        $this->connection->executeStatement($sql, [
            'fileImportId' => (string) $fileImport->getId(),
            'originalFilename' => $fileImport->getOriginalFilename(),
            'fileHash' => $fileImport->getFileHash(),
            'fileContents' => null !== $fileImport->getFileContents() ? (string) CompressedString::fromUncompressed($fileImport->getFileContents()) : null,
            'source' => $fileImport->getSource()->value,
            'status' => $fileImport->getStatus()->value,
            'errorMessage' => $fileImport->getErrorMessage(),
            'activityId' => $fileImport->getActivityId() instanceof ActivityId ? (string) $fileImport->getActivityId() : null,
            'importedOn' => $fileImport->getImportedOn(),
        ]);
    }

    public function existsForFileHash(string $fileHash): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(*)')
            ->from('FileImport')
            ->andWhere('fileHash = :fileHash')
            ->setParameter('fileHash', $fileHash);

        return (int) $queryBuilder->executeQuery()->fetchOne() > 0;
    }

    public function findAll(): FileImports
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('FileImport')
            ->orderBy('importedOn', 'DESC');

        return FileImports::fromArray(array_map(
            $this->hydrate(...),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): FileImport
    {
        return FileImport::fromState(
            fileImportId: FileImportId::fromString($result['fileImportId']),
            originalFilename: $result['originalFilename'],
            fileHash: $result['fileHash'],
            fileContents: null !== $result['fileContents']
                ? CompressedString::fromCompressed($result['fileContents'])->uncompress()
                : null,
            source: ImportSource::from($result['source']),
            status: FileImportStatus::from($result['status']),
            errorMessage: $result['errorMessage'],
            activityId: ActivityId::fromOptionalString($result['activityId']),
            importedOn: SerializableDateTime::fromString($result['importedOn']),
        );
    }
}
