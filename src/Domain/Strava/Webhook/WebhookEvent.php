<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookEvent
{
    /**
     * @param array<string, mixed> $updates
     */
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

    /**
     * @param array<string, mixed> $payload
     */
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

    /**
     * @return array<string, mixed>
     */
    public function getUpdates(): array
    {
        return $this->updates;
    }

    public function isActivityEvent(): bool
    {
        return 'activity' === $this->objectType;
    }

    public function isAthleteEvent(): bool
    {
        return 'athlete' === $this->objectType;
    }

    public function isCreateEvent(): bool
    {
        return 'create' === $this->aspectType;
    }

    public function isUpdateEvent(): bool
    {
        return 'update' === $this->aspectType;
    }

    public function isDeleteEvent(): bool
    {
        return 'delete' === $this->aspectType;
    }

    public function shouldTriggerImport(): bool
    {
        // Trigger import for new activities or activity updates
        return $this->isActivityEvent() && ($this->isCreateEvent() || $this->isUpdateEvent());
    }
}
