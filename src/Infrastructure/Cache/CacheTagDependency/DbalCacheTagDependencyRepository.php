<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\CacheTagDependency;

use App\Infrastructure\Repository\DbalRepository;

final readonly class DbalCacheTagDependencyRepository extends DbalRepository implements CacheTagDependencyRepository
{
    public function register(CacheTagDependency $dependency): void
    {
        $this->connection->executeStatement(
            'INSERT INTO CacheTagDependency (entityType, entityId, dependsOnTag)
             VALUES (:entityType, :entityId, :dependsOnTag)
             ON CONFLICT(entityType, entityId, dependsOnTag) DO NOTHING',
            [
                'entityType' => $dependency->getEntityType(),
                'entityId' => $dependency->getEntityId(),
                'dependsOnTag' => $dependency->getDependsOnTag(),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function findEntityIdsThatDependOnInvalidatedTags(string $entityType): array
    {
        return $this->connection->executeQuery(
            'SELECT DISTINCT entityId
             FROM CacheTagDependency
             INNER JOIN InvalidatedCacheTag ON CacheTagDependency.dependsOnTag = InvalidatedCacheTag.tag
             WHERE entityType = :entityType',
            ['entityType' => $entityType]
        )->fetchFirstColumn();
    }

    public function clearForEntity(string $entityType, string $entityId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM CacheTagDependency WHERE entityType = :entityType AND entityId = :entityId',
            [
                'entityType' => $entityType,
                'entityId' => $entityId,
            ]
        );
    }
}
