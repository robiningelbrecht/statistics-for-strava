<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\Webhook\DbalWebhookEventRepository;
use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class DbalWebhookEventRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private WebhookEventRepository $webhookEventRepository;

    public function testAddAndGrab(): void
    {
        $event = WebhookEvent::create(
            objectId: '1',
            objectType: 'activity',
            payload: [],
        );

        $this->webhookEventRepository->add($event);
        $this->webhookEventRepository->add($event);

        $event = WebhookEvent::create(
            objectId: '2',
            objectType: 'activity',
            payload: [],
        );

        $this->webhookEventRepository->add($event);

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()->executeQuery('SELECT * FROM StravaWebhookEvent')->fetchAllAssociative())
        );

        $this->assertEquals(
            [
                WebhookEvent::create(
                    objectId: '1',
                    objectType: 'activity',
                    payload: [],
                ),
                WebhookEvent::create(
                    objectId: '2',
                    objectType: 'activity',
                    payload: [],
                ),
            ],
            $this->webhookEventRepository->grab()
        );

        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM StravaWebhookEvent')->fetchAllAssociative()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookEventRepository = new DbalWebhookEventRepository($this->getConnection());
    }
}
