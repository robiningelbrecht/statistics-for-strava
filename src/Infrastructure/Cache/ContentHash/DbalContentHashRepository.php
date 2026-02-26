<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\ContentHash;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;

final readonly class DbalContentHashRepository extends DbalRepository implements ContentHashRepository
{
    public function save(ContentHash $contentHash): void
    {
        $this->connection->executeStatement(
            'INSERT INTO ContentHash (entityType, entityId, hash)
             VALUES (:entityType, :entityId, :hash)
             ON CONFLICT(entityType, entityId) DO UPDATE SET hash = :hash',
            [
                'entityType' => $contentHash->getEntityType(),
                'entityId' => $contentHash->getEntityId(),
                'hash' => $contentHash->getHash(),
            ]
        );
    }

    public function find(string $entityType, string $entityId): string
    {
        $result = $this->connection->executeQuery(
            'SELECT hash FROM ContentHash WHERE entityType = :entityType AND entityId = :entityId',
            [
                'entityType' => $entityType,
                'entityId' => $entityId,
            ]
        )->fetchOne();

        if (false === $result) {
            throw new EntityNotFound(sprintf('ContentHash for %s "%s" not found', $entityType, $entityId));
        }

        return $result;
    }

    public function clearForEntity(string $entityType, string $entityId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ContentHash WHERE entityType = :entityType AND entityId = :entityId',
            [
                'entityType' => $entityType,
                'entityId' => $entityId,
            ]
        );
    }
}
