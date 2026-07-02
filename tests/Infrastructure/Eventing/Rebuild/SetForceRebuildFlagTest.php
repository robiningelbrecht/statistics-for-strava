<?php

namespace App\Tests\Infrastructure\Eventing\Rebuild;

use App\Infrastructure\Eventing\Rebuild\SetForceRebuildFlag;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use PHPUnit\Framework\TestCase;

class SetForceRebuildFlagTest extends TestCase
{
    public function testItFlagsForceRebuild(): void
    {
        $keyValueStore = $this->createMock(KeyValueStore::class);
        $keyValueStore
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (KeyValue $keyValue): bool => Key::FORCE_REBUILD === $keyValue->getKey()
                    && '1' === (string) $keyValue->getValue()
            ));

        $listener = new SetForceRebuildFlag($keyValueStore);
        $listener();
    }
}
