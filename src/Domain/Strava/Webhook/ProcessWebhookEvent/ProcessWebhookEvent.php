<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Infrastructure\CQRS\Command\Command;

final readonly class ProcessWebhookEvent extends Command
{
    public function __construct(
        private array $eventPayload,
    ) {
    }

    public function getEventPayload(): array
    {
        return $this->eventPayload;
    }
}

