<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use Doctrine\DBAL\Connection;

final readonly class Mutex
{
    private const int TTL_IN_SECONDS = 3600;
    private const string KEY_SUFFIX = 'Lock';

    public function __construct(
        private Connection $connection,
        private Clock $clock,
    ) {
    }

    public function acquireLock(string $key): bool
    {
        $realKey = $key.self::KEY_SUFFIX;
        $now = $this->clock->getCurrentDateTimeImmutable()->getTimestamp();

        $this->connection->beginTransaction();

        [$isLocked, $timestamp] = Json::decode($this->connection->executeQuery(
            'SELECT `value` FROM KeyValue WHERE `key` = :key',
            [
                'key' => $realKey,
            ]
        )->fetchOne() ?? 'null') ?? ['isLocked' => false, 'timestamp' => 0];

        $expired = $isLocked && ($now - $timestamp > self::TTL_IN_SECONDS);

        if (!$isLocked || $expired) {
            // Acquire or steal lock.
            $lock = ['isLocked' => true, 'timestamp' => $now];

            $this->connection->executeStatement('UPDATE KeyValue SET `value` = :value WHERE `key` = :key', [
                'value' => Json::encode($lock),
                'key' => $realKey,
            ]);

            $this->connection->commit();

            return true;
        }

        // Already locked and not expired
        $this->connection->rollBack();

        return false;
    }

    public function releaseLock(string $key): void
    {
        $realKey = $key.self::KEY_SUFFIX;
        $now = $this->clock->getCurrentDateTimeImmutable()->getTimestamp();

        $this->connection->executeStatement('UPDATE KeyValue SET `value` = :value WHERE `key` = :key', [
            'value' => Json::encode(['isLocked' => true, 'timestamp' => $now]),
            'key' => $realKey,
        ]);
    }
}
