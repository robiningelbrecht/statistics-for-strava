<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookEvent
{
    private function __construct(
        private string $objectType,
        private int $objectId,
        private string $aspectType,
        private int $ownerId,
        private int $subscriptionId,
        private int $eventTime,
        private array $updates,
    ) {
    }

    public static function fromWebhookPayload(array $payload): self
    {
        return new self(
            objectType: $payload['object_type'] ?? '',
            objectId: $payload['object_id'] ?? 0,
            aspectType: $payload['aspect_type'] ?? '',
            ownerId: $payload['owner_id'] ?? 0,
            subscriptionId: $payload['subscription_id'] ?? 0,
            eventTime: $payload['event_time'] ?? 0,
            updates: $payload['updates'] ?? [],
        );
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getAspectType(): string
    {
        return $this->aspectType;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getEventTime(): int
    {
        return $this->eventTime;
    }

    public function getUpdates(): array
    {
        return $this->updates;
    }

    public function isActivityEvent(): bool
    {
        return $this->objectType === 'activity';
    }

    public function isAthleteEvent(): bool
    {
        return $this->objectType === 'athlete';
    }

    public function isCreateEvent(): bool
    {
        return $this->aspectType === 'create';
    }

    public function isUpdateEvent(): bool
    {
        return $this->aspectType === 'update';
    }

    public function isDeleteEvent(): bool
    {
        return $this->aspectType === 'delete';
    }

    public function shouldTriggerImport(): bool
    {
        // Trigger import for new activities or activity updates
        return $this->isActivityEvent() && ($this->isCreateEvent() || $this->isUpdateEvent());
    }
}

