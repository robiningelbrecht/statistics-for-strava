<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\RequiresRebuild;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Eventing\Rebuild\RebuildIsRequired;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class PublishRebuildIsRequiredMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EventBus $eventBus,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $command = $envelope->getMessage();
        $envelope = $stack->next()->handle($envelope, $stack);

        if ([] !== new \ReflectionClass($command)->getAttributes(RequiresRebuild::class)) {
            $this->eventBus->publishEvents([new RebuildIsRequired()]);
        }

        return $envelope;
    }
}
