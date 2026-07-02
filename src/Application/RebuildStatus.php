<?php

declare(strict_types=1);

namespace App\Application;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;

final readonly class RebuildStatus
{
    public function __construct(
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function isPending(): bool
    {
        try {
            $this->keyValueStore->find(Key::FORCE_REBUILD);

            return true;
        } catch (EntityNotFound) {
            return false;
        }
    }
}
