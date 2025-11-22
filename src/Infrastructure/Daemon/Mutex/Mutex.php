<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Mutex;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use Doctrine\DBAL\Connection;

final class Mutex
{
    /** @var array<string, bool> */
    private static array $shutdownRegistered = [];

    private const int STALE_THRESHOLD_IN_SECONDS = 900; // 15 minutes.
    private const string HEARTBEAT_KEY = 'heartbeat';
    private const string LOCK_ACQUIRED_BY_KEY = 'lockAcquiredBy';

    public function __construct(
        private readonly Connection $connection,
        private readonly Clock $clock,
    ) {
    }

    public function acquire(string $lockName, string $lockAcquiredBy): void
    {
        $key = $this->key($lockName);
        $now = $this->clock->getCurrentDateTimeImmutable()->getTimestamp();

        $this->connection->beginTransaction();

        $row = $this->connection->fetchOne(
            'SELECT value FROM KeyValue WHERE key = :key',
            ['key' => $key]
        );

        if (false === $row) {
            $this->updateLockRow($key, $now, $lockAcquiredBy);
            $this->connection->commit();

            // Register release on shutdown.
            $this->registerShutdownOnce($lockName);

            return;
        }

        $data = Json::decode($row);
        $heartbeat = $data[self::HEARTBEAT_KEY];
        $isStale = $now - $heartbeat > self::STALE_THRESHOLD_IN_SECONDS;

        if ($isStale) {
            $this->updateLockRow($key, $now, $lockAcquiredBy);
            $this->connection->commit();
            // Register release on shutdown.
            $this->registerShutdownOnce($lockName);

            return;
        }

        $this->connection->rollBack();
        throw new LockIsAlreadyAcquired($lockName);
    }

    public function heartbeat(string $lockName): void
    {
        $key = $this->key($lockName);
        $row = $this->connection->fetchOne(
            'SELECT value FROM KeyValue WHERE key = :key',
            ['key' => $key]
        );

        if (false === $row) {
            throw new \RuntimeException(sprintf('Cannot heartbeat: lock "%s" does not exist', $lockName));
        }

        $now = $this->clock->getCurrentDateTimeImmutable()->getTimestamp();
        $this->updateLockRow($key, $now, Json::decode($row)[self::LOCK_ACQUIRED_BY_KEY]);
    }

    public function release(string $lockName): void
    {
        $key = $this->key($lockName);
        $this->connection->executeStatement(
            'DELETE FROM KeyValue WHERE key = :key',
            ['key' => $key]
        );
    }

    private function updateLockRow(string $key, int $timestamp, string $lockAcquiredBy): void
    {
        $value = Json::encode([
            self::HEARTBEAT_KEY => $timestamp,
            self::LOCK_ACQUIRED_BY_KEY => $lockAcquiredBy,
        ]);

        $this->connection->executeStatement(
            'INSERT INTO KeyValue (key, value) VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = :value',
            ['key' => $key, 'value' => $value]
        );
    }

    private function registerShutdownOnce(string $lockName): void
    {
        if (isset(self::$shutdownRegistered[$lockName])) {
            return;
        }

        self::$shutdownRegistered[$lockName] = true;
        register_shutdown_function(fn () => $this->release($lockName));
    }

    private function key(string $lockName): string
    {
        return 'lock.'.$lockName;
    }
}
