<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Bus\PublishRebuildIsRequiredMiddleware;
use App\Infrastructure\Eventing\Rebuild\RebuildIsRequired;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperationThatRequiresRebuild\RunAnOperationThatRequiresRebuild;
use App\Tests\Infrastructure\Eventing\SpyEventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class PublishRebuildIsRequiredMiddlewareTest extends TestCase
{
    public function testItPublishesRebuildIsRequiredForATaggedCommand(): void
    {
        $eventBus = new SpyEventBus();
        $middleware = new PublishRebuildIsRequiredMiddleware($eventBus);

        $middleware->handle(new Envelope(new RunAnOperationThatRequiresRebuild()), $this->stackThatSucceeds());

        $publishedEvents = $eventBus->getPublishedEvents();
        $this->assertCount(1, $publishedEvents);
        $this->assertInstanceOf(RebuildIsRequired::class, $publishedEvents[0]);
    }

    public function testItDoesNotPublishForAnUntaggedCommand(): void
    {
        $eventBus = new SpyEventBus();
        $middleware = new PublishRebuildIsRequiredMiddleware($eventBus);

        $middleware->handle(new Envelope(new RunAnOperation('test')), $this->stackThatSucceeds());

        $this->assertEmpty($eventBus->getPublishedEvents());
    }

    public function testItDoesNotPublishWhenTheCommandFails(): void
    {
        $eventBus = new SpyEventBus();
        $middleware = new PublishRebuildIsRequiredMiddleware($eventBus);

        try {
            $middleware->handle(new Envelope(new RunAnOperationThatRequiresRebuild()), $this->stackThatThrows());
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException) {
            // Expected: a failing command must not flag a rebuild.
        }

        $this->assertEmpty($eventBus->getPublishedEvents());
    }

    private function stackThatSucceeds(): StackInterface
    {
        return new class implements StackInterface, MiddlewareInterface {
            public function next(): MiddlewareInterface
            {
                return $this;
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                return $envelope;
            }
        };
    }

    private function stackThatThrows(): StackInterface
    {
        return new class implements StackInterface, MiddlewareInterface {
            public function next(): MiddlewareInterface
            {
                return $this;
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                throw new \RuntimeException('Command failed');
            }
        };
    }
}
