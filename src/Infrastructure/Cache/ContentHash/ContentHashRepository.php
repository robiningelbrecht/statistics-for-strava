<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\ContentHash;

interface ContentHashRepository
{
    public function save(ContentHash $contentHash): void;

    public function find(string $entityType, string $entityId): string;

    public function clearForEntity(string $entityType, string $entityId): void;
}
