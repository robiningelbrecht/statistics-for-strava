<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;

final readonly class DbalWebhookEventRepository extends DbalRepository implements WebhookEventRepository
{
    public function add(WebhookEvent $webhookEvent): void
    {
        $sql = 'INSERT INTO StravaWebhookEvent (objectId, objectType, payload)
        VALUES (:objectId, :objectType, :payload)
        ON CONFLICT(objectId, objectType) DO NOTHING;';

        $this->connection->executeStatement($sql, [
            'objectId' => $webhookEvent->getObjectId(),
            'objectType' => $webhookEvent->getObjectType(),
            'payload' => Json::encode($webhookEvent->getPayload()),
        ]);
    }

    public function grab(): array
    {
        $this->connection->beginTransaction();

        $results = $this->connection->executeQuery('SELECT * FROM StravaWebhookEvent')->fetchAllAssociative();
        $this->connection->executeStatement('DELETE FROM StravaWebhookEvent');

        $this->connection->commit();

        return array_map(
            fn (array $result): WebhookEvent => WebhookEvent::create(
                objectId: $result['objectId'],
                objectType: $result['objectType'],
                payload: Json::decode($result['payload'])
            ),
            $results,
        );
    }
}
