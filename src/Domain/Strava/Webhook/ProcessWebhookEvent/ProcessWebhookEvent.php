<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class ProcessWebhookEvent extends DomainCommand
{
    /**
     * @param array<string, mixed> $eventPayload
     */
    public function __construct(
        private array $eventPayload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventPayload(): array
    {
        return $this->eventPayload;
    }
}
