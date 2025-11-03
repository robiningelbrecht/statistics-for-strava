<?php

namespace App\Tests\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Domain\Strava\Webhook\ProcessWebhookEvent\ProcessWebhookEvent;
use PHPUnit\Framework\TestCase;

class ProcessWebhookEventTest extends TestCase
{
    public function testGetEventPayload(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 123,
            'aspect_type' => 'create',
        ];

        $command = new ProcessWebhookEvent($payload);

        $this->assertEquals($payload, $command->getEventPayload());
    }

    public function testJsonSerialize(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 123,
        ];

        $command = new ProcessWebhookEvent($payload);
        $serialized = $command->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('commandName', $serialized);
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertEquals(ProcessWebhookEvent::class, $serialized['commandName']);
    }
}

