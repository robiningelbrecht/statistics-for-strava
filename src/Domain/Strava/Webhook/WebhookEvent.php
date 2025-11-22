<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        private string $objectId,
        private string $objectType,
        private array $payload,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function create(
        string $objectId,
        string $objectType,
        array $payload,
    ): self {
        return new self(
            objectId: $objectId,
            objectType: $objectType,
            payload: $payload,
        );
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
