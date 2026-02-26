<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\InvalidatedCacheTag;

use App\Infrastructure\Repository\DbalRepository;

final readonly class DbalInvalidatedCacheTagRepository extends DbalRepository implements InvalidatedCacheTagRepository
{
    public function invalidate(string ...$tags): void
    {
        if ($tags === []) {
            return;
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_values($tags) as $i => $tag) {
            $placeholders[] = "(:tag{$i})";
            $parameters["tag{$i}"] = $tag;
        }

        $this->connection->executeStatement(
            'INSERT INTO InvalidatedCacheTag (tag) VALUES '.implode(', ', $placeholders).' ON CONFLICT(tag) DO NOTHING',
            $parameters
        );
    }

    public function hasAnyWithPrefix(string $prefix): bool
    {
        return (bool) $this->connection->executeQuery(
            'SELECT 1 FROM InvalidatedCacheTag WHERE tag LIKE :prefix LIMIT 1',
            ['prefix' => $prefix.'%']
        )->fetchOne();
    }

    public function clearAll(): void
    {
        $this->connection->executeStatement('DELETE FROM InvalidatedCacheTag');
    }
}
