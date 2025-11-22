<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\Webhook\DbalWebhookEventRepository;
use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
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

        $this->assertTrue($this->webhookEventRepository->grab());
        $this->assertFalse($this->webhookEventRepository->grab());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookEventRepository = new DbalWebhookEventRepository($this->getConnection());
    }
}
