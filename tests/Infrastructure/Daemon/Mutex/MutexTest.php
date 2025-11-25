<?php

namespace App\Tests\Infrastructure\Daemon\Mutex;

use App\Infrastructure\Daemon\Mutex\LockIsAlreadyAcquired;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;

class MutexTest extends ContainerTestCase
{
    private Mutex $mutex;

    public function testAcquireWhenNewLock(): void
    {
        $this->mutex->acquire(
            lockName: 'import',
            lockAcquiredBy: 'myProcess'
        );

        $this->mutex->release('import');

        $this->mutex->acquire(
            lockName: 'import',
            lockAcquiredBy: 'myProcess'
        );

        $this->expectExceptionObject(new LockIsAlreadyAcquired(
            name: 'import',
            lockAcquiredBy: 'myProcess',
        ));

        $this->mutex->acquire(
            lockName: 'import',
            lockAcquiredBy: 'myProcess'
        );
    }

    public function testAcquireWhenLockIsStale(): void
    {
        $this->getConnection()->executeStatement('INSERT INTO KeyValue (key, value) VALUES (:key, :value)', [
            'key' => 'lock.import',
            'value' => Json::encode([
                'heartbeat' => 1,
                'lockAcquiredBy' => 'myProcess',
            ]),
        ]);

        $this->mutex->acquire(
            lockName: 'import',
            lockAcquiredBy: 'myProcess'
        );
        $this->addToAssertionCount(1);
    }

    public function testHeartBeat(): void
    {
        $this->getConnection()->executeStatement('INSERT INTO KeyValue (key, value) VALUES (:key, :value)', [
            'key' => 'lock.import',
            'value' => Json::encode([
                'heartbeat' => 1,
                'lockAcquiredBy' => 'myProcess',
            ]),
        ]);

        $this->mutex->heartbeat('import');

        $this->expectExceptionObject(new LockIsAlreadyAcquired(
            name: 'import',
            lockAcquiredBy: 'myProcess',
        ));

        $this->mutex->acquire(
            lockName: 'import',
            lockAcquiredBy: 'myProcess'
        );
    }

    public function testHeartBeatWithUnexistingLock(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Cannot heartbeat: lock "import" does not exist'));

        $this->mutex->heartbeat('import');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutex = new Mutex(
            $this->getConnection(),
            PausedClock::fromString('2025-11-01 10:00:00')
        );
    }
}
