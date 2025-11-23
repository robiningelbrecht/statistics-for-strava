<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

final readonly class WebhookEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        // @phpstan-ignore property.onlyWritten
        private string $objectId,
        // @phpstan-ignore property.onlyWritten
        private string $objectType,
        // @phpstan-ignore property.onlyWritten
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
}
