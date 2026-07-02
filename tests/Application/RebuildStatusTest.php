<?php

namespace App\Tests\Application;

use App\Application\RebuildStatus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use PHPUnit\Framework\TestCase;

class RebuildStatusTest extends TestCase
{
    public function testItIsPendingWhenTheFlagIsSet(): void
    {
        $keyValueStore = $this->createMock(KeyValueStore::class);
        $keyValueStore
            ->expects($this->once())
            ->method('find')
            ->with(Key::FORCE_REBUILD)
            ->willReturn(Value::fromString('1'));

        $this->assertTrue(new RebuildStatus($keyValueStore)->isPending());
    }

    public function testItIsNotPendingWhenTheFlagIsNotSet(): void
    {
        $keyValueStore = $this->createMock(KeyValueStore::class);
        $keyValueStore
            ->expects($this->once())
            ->method('find')
            ->with(Key::FORCE_REBUILD)
            ->willThrowException(new EntityNotFound('not found'));

        $this->assertFalse(new RebuildStatus($keyValueStore)->isPending());
    }
}
