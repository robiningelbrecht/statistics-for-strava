<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\InvalidatedCacheTag;

use App\Infrastructure\Cache\Tag;

interface InvalidatedCacheTagRepository
{
    public function invalidate(Tag ...$tags): void;

    public function hasAnyWithPrefix(string $prefix): bool;

    public function clearAll(): void;
}
