<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Repository\Overview;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalFileImportOverviewRepository extends DbalRepository implements FileImportOverviewRepository
{
    public function find(Pagination $pagination): Overview
    {
        $results = $this->connection->createQueryBuilder()
            ->select('fileImportId', 'originalFilename', 'source', 'status', 'errorMessage', 'activityId', 'importedOn')
            ->from('FileImport')
            ->orderBy('importedOn', 'DESC')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit())
            ->executeQuery()
            ->fetchAllAssociative();

        $total = (int) $this->connection
            ->executeQuery('SELECT COUNT(*) FROM FileImport')
            ->fetchOne();

        return Overview::create(
            pagination: $pagination,
            total: $total,
            items: array_map($this->hydrate(...), $results),
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): FileImportOverviewItem
    {
        return FileImportOverviewItem::fromState(
            fileImportId: FileImportId::fromString($result['fileImportId']),
            originalFilename: $result['originalFilename'],
            source: ImportSource::from($result['source']),
            status: FileImportStatus::from($result['status']),
            errorMessage: $result['errorMessage'],
            activityId: ActivityId::fromOptionalString($result['activityId']),
            importedOn: SerializableDateTime::fromString($result['importedOn']),
        );
    }
}
