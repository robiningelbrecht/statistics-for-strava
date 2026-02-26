<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\CacheTagDependency;

interface CacheTagDependencyRepository
{
    public function register(CacheTagDependency $dependency): void;

    /**
     * @return string[]
     */
    public function findEntityIdsThatDependOnInvalidatedTags(string $entityType): array;

    public function clearForEntity(string $entityType, string $entityId): void;
}
