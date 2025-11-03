<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class ProcessWebhookEvent extends DomainCommand
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

