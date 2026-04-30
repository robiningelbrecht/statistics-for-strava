<?php

declare(strict_types=1);

namespace App\Domain\Activity\ImportHash;

use App\Domain\Activity\ActivityId;
use App\Infrastructure\Repository\DbalRepository;

final readonly class DbalActivityImportHashRepository extends DbalRepository implements ActivityImportHashRepository
{
    public function find(ActivityId $activityId): ?ActivityImportHash
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityImportHash')
            ->where('activityId = :activityId')
            ->setParameter('activityId', (string) $activityId);

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (!$result) {
            return null;
        }

        return ActivityImportHash::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            hash: $result['hash'],
        );
    }

    public function save(ActivityImportHash $activityImportHash): void
    {
        $sql = 'INSERT INTO ActivityImportHash (activityId, hash)
                VALUES (:activityId, :hash)
                ON CONFLICT(activityId) DO UPDATE SET hash = :hash';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityImportHash->getActivityId(),
            'hash' => $activityImportHash->getHash(),
        ]);
    }
}
