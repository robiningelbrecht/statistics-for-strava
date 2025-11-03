<?php

namespace App\Tests\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Domain\Strava\Webhook\ProcessWebhookEvent\ProcessWebhookEvent;
use App\Domain\Strava\Webhook\ProcessWebhookEvent\ProcessWebhookEventCommandHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProcessWebhookEventCommandHandlerTest extends TestCase
{
    private ProcessWebhookEventCommandHandler $handler;
    private MockObject $logger;

    public function testHandleActivityCreateEvent(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 123456,
            'aspect_type' => 'create',
            'owner_id' => 789,
            'subscription_id' => 999,
            'event_time' => 1516126040,
        ];

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = new ProcessWebhookEvent($payload);
        $this->handler->handle($command);

        // Test passes if no exception thrown
        $this->assertTrue(true);
    }

    public function testHandleActivityUpdateEvent(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 123456,
            'aspect_type' => 'update',
            'owner_id' => 789,
            'updates' => ['title' => 'New Title'],
        ];

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $command = new ProcessWebhookEvent($payload);
        $this->handler->handle($command);

        $this->assertTrue(true);
    }

    public function testHandleActivityDeleteEventDoesNotTriggerImport(): void
    {
        $payload = [
            'object_type' => 'activity',
            'object_id' => 123456,
            'aspect_type' => 'delete',
            'owner_id' => 789,
        ];

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $command = new ProcessWebhookEvent($payload);
        $this->handler->handle($command);

        $this->assertTrue(true);
    }

    public function testHandleAthleteEventDoesNotTriggerImport(): void
    {
        $payload = [
            'object_type' => 'athlete',
            'object_id' => 789,
            'aspect_type' => 'update',
            'updates' => ['authorized' => 'false'],
        ];

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $command = new ProcessWebhookEvent($payload);
        $this->handler->handle($command);

        $this->assertTrue(true);
    }

    protected function setUp(): void
    {
        $this->handler = new ProcessWebhookEventCommandHandler(
            $this->logger = $this->createMock(LoggerInterface::class),
            '/tmp/test-project',
        );
    }
}
