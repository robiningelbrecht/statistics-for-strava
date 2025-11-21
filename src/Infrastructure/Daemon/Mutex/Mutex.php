<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Mutex;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class Mutex
{
    private const string KEY_SUFFIX = 'Lock';
    private ?string $activeLock = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly Clock $clock,
    ) {
    }

    public function guard(string $name, callable $callback, int $ttl = 3600): mixed
    {
        if (!$this->acquireLock($name, $ttl)) {
            throw new LockIsAlreadyAcquired($name);
        }

        try {
            return $callback();
        } finally {
            $this->releaseLock($name);
        }
    }

    private function acquireLock(string $name, int $ttl = 3600): bool
    {
        $key = $name.self::KEY_SUFFIX;
        $now = $this->clock->getCurrentDateTimeImmutable()->getTimestamp();

        // Remove stale lock entry based on TTL.
        $this->connection->executeStatement(
            'DELETE FROM KeyValue 
             WHERE `key` = :key 
             AND CAST(JSON_EXTRACT(value, "$.lockedAt") AS INTEGER) < :expired',
            [
                'key' => $key,
                'expired' => $now - $ttl,
            ]
        );

        try {
            $this->connection->insert('KeyValue', [
                'key' => $key,
                'value' => Json::encode(['locked_at' => $now]),
            ]);

            // Track the active lock so shutdown can release it.
            $this->activeLock = $key;

            register_shutdown_function(function () {
                if (null !== $this->activeLock) {
                    $this->releaseLock($this->activeLock);
                }
            });

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    private function releaseLock(string $name): void
    {
        $this->connection->delete('KeyValue', ['key' => $name.self::KEY_SUFFIX]);

        if ($this->activeLock === $name) {
            $this->activeLock = null;
        }
    }
}
