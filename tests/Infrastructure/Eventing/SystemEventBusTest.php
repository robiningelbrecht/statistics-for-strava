<?php

namespace App\Tests\Infrastructure\Eventing;

use App\Infrastructure\Eventing\SystemEventBus;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SystemEventBusTest extends KernelTestCase
{
    use MatchesSnapshots;

    private \PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    public function testPublishEvents(): void
    {
        $eventBus = new SystemEventBus($this->eventDispatcher);

        $event = new ADomainEvent();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $event,
                ADomainEvent::class
            );

        $eventBus->publishEvents([$event]);
    }

    public function testItRegistersDomainEventListeners(): void
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get(EventDispatcherInterface::class);
        $listeners = $eventDispatcher->getListeners();

        $snapshot = [];

        foreach ($listeners as $listenerCallbacks) {
            foreach ($listenerCallbacks as $callback) {
                if (!str_starts_with($callback[0]::class, 'App\Domain')) {
                    continue;
                }
                $snapshot[$callback[0]::class][] = $callback[1];
            }
        }

        $this->assertMatchesJsonSnapshot($snapshot);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }
}
