<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing\Rebuild;

use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: RebuildIsRequired::class)]
final readonly class SetForceRebuildFlag
{
    public function __construct(
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function __invoke(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::FORCE_REBUILD,
            value: Value::fromString('1'),
        ));
    }
}
