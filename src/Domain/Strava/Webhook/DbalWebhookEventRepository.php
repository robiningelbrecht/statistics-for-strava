<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;

final readonly class DbalWebhookEventRepository extends DbalRepository implements WebhookEventRepository
{
    private const string WEBHOOK_EVENT_KEY = 'needsToProcessWebhooks';

    public function add(WebhookEvent $webhookEvent): void
    {
        $sql = 'INSERT INTO KeyValue (`key`, `value`)
        VALUES (:key, :value)
        ON CONFLICT(`key`) DO NOTHING;';

        $this->connection->executeStatement($sql, [
            'key' => self::WEBHOOK_EVENT_KEY,
            'value' => Json::encode(true),
        ]);
    }

    public function grab(): bool
    {
        $this->connection->beginTransaction();

        $value = $this->connection->executeQuery(
            'SELECT `value` FROM KeyValue WHERE `key`= :key',
            ['key' => self::WEBHOOK_EVENT_KEY]
        )->fetchOne();

        $this->connection->executeStatement('DELETE FROM KeyValue WHERE `key`= :key', [
            'key' => self::WEBHOOK_EVENT_KEY,
        ]);

        $this->connection->commit();

        return (bool) $value;
    }
}
