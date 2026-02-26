<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\InvalidatedCacheTag;

interface InvalidatedCacheTagRepository
{
    public function invalidate(string ...$tags): void;

    public function hasAnyWithPrefix(string $prefix): bool;

    public function clearAll(): void;
}
