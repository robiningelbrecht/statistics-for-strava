<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Domain\Strava\Webhook\WebhookEvent;
use App\Domain\Strava\Webhook\WebhookEventRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class ProcessWebhookEventCommandHandler implements CommandHandler
{
    public function __construct(
        private WebhookEventRepository $webhookEventRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ProcessWebhookEvent);

        $payload = $command->getEventPayload();

        if ('activity' !== $payload['object_type']) {
            return;
        }

        $this->webhookEventRepository->add(WebhookEvent::create(
            objectId: (string) $payload['object_id'],
            objectType: $payload['object_type'],
            payload: $payload,
        ));
    }
}
