<?php

namespace App\Tests\Domain\Strava\Webhook;

use App\Domain\Strava\Webhook\WebhookEvent;
use PHPUnit\Framework\TestCase;

class WebhookEventTest extends TestCase
{
    public function testFromWebhookPayload(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 1360128428,
            'aspect_type' => 'create',
            'owner_id' => 134815,
            'subscription_id' => 120475,
            'event_time' => 1516126040,
            'updates' => ['title' => 'New Title'],
        ];

        $event = WebhookEvent::fromWebhookPayload($payload);

        $this->assertEquals('activity', $event->getObjectType());
        $this->assertEquals(1360128428, $event->getObjectId());
        $this->assertEquals('create', $event->getAspectType());
        $this->assertEquals(134815, $event->getOwnerId());
        $this->assertEquals(120475, $event->getSubscriptionId());
        $this->assertEquals(1516126040, $event->getEventTime());
        $this->assertEquals(['title' => 'New Title'], $event->getUpdates());
    }

    public function testFromWebhookPayloadWithDefaults(): void
    {
        $event = WebhookEvent::fromWebhookPayload([]);

        $this->assertEquals('', $event->getObjectType());
        $this->assertEquals(0, $event->getObjectId());
        $this->assertEquals('', $event->getAspectType());
        $this->assertEquals(0, $event->getOwnerId());
        $this->assertEquals(0, $event->getSubscriptionId());
        $this->assertEquals(0, $event->getEventTime());
        $this->assertEquals([], $event->getUpdates());
    }

    public function testIsActivityEvent(): void
    {
        $activityEvent = WebhookEvent::fromWebhookPayload(['object_type' => 'activity']);
        $this->assertTrue($activityEvent->isActivityEvent());

        $athleteEvent = WebhookEvent::fromWebhookPayload(['object_type' => 'athlete']);
        $this->assertFalse($athleteEvent->isActivityEvent());
    }

    public function testIsAthleteEvent(): void
    {
        $athleteEvent = WebhookEvent::fromWebhookPayload(['object_type' => 'athlete']);
        $this->assertTrue($athleteEvent->isAthleteEvent());

        $activityEvent = WebhookEvent::fromWebhookPayload(['object_type' => 'activity']);
        $this->assertFalse($activityEvent->isAthleteEvent());
    }

    public function testIsCreateEvent(): void
    {
        $createEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'create']);
        $this->assertTrue($createEvent->isCreateEvent());

        $updateEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'update']);
        $this->assertFalse($updateEvent->isCreateEvent());
    }

    public function testIsUpdateEvent(): void
    {
        $updateEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'update']);
        $this->assertTrue($updateEvent->isUpdateEvent());

        $createEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'create']);
        $this->assertFalse($createEvent->isUpdateEvent());
    }

    public function testIsDeleteEvent(): void
    {
        $deleteEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'delete']);
        $this->assertTrue($deleteEvent->isDeleteEvent());

        $createEvent = WebhookEvent::fromWebhookPayload(['aspect_type' => 'create']);
        $this->assertFalse($createEvent->isDeleteEvent());
    }

    public function testShouldTriggerImport(): void
    {
        $activityCreate = WebhookEvent::fromWebhookPayload([
            'object_type' => 'activity',
            'aspect_type' => 'create',
        ]);
        $this->assertTrue($activityCreate->shouldTriggerImport());

        $activityUpdate = WebhookEvent::fromWebhookPayload([
            'object_type' => 'activity',
            'aspect_type' => 'update',
        ]);
        $this->assertTrue($activityUpdate->shouldTriggerImport());

        $activityDelete = WebhookEvent::fromWebhookPayload([
            'object_type' => 'activity',
            'aspect_type' => 'delete',
        ]);
        $this->assertFalse($activityDelete->shouldTriggerImport());

        $athleteEvent = WebhookEvent::fromWebhookPayload([
            'object_type' => 'athlete',
            'aspect_type' => 'update',
        ]);
        $this->assertFalse($athleteEvent->shouldTriggerImport());
    }
}
