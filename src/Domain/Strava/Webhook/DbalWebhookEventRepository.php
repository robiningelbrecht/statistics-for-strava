<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\ArrayParameterType;

final readonly class DbalWebhookEventRepository extends DbalRepository implements WebhookEventRepository
{
    public function add(WebhookEvent $webhookEvent): void
    {
        $sql = 'INSERT INTO WebhookEvent (objectId, objectType, aspectType, payload) 
                VALUES (:objectId, :objectType, :aspectType, :payload)
                ON CONFLICT(`objectId`) DO NOTHING;';

        $this->connection->executeStatement($sql, [
            'objectId' => $webhookEvent->getObjectId(),
            'objectType' => $webhookEvent->getObjectType(),
            'aspectType' => $webhookEvent->getAspectType()->value,
            'payload' => Json::encode($webhookEvent->getPayload()),
        ]);
    }

    public function grab(): array
    {
        $this->connection->beginTransaction();

        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('WebhookEvent');

        $webhookEvents = array_map(
            fn (array $result): WebhookEvent => WebhookEvent::create(
                objectId: $result['objectId'],
                objectType: $result['objectType'],
                aspectType: WebhookAspectType::from($result['aspectType']),
                payload: Json::decode($result['payload']),
            ),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );

        $this->connection->executeStatement('DELETE FROM WebhookEvent WHERE objectId IN (:objectIds)',
            [
                'objectIds' => array_map(fn (WebhookEvent $webhookEvent): string => $webhookEvent->getObjectId(), $webhookEvents),
            ],
            [
                'objectIds' => ArrayParameterType::STRING,
            ]
        );

        $this->connection->commit();

        return $webhookEvents;
    }
}
