<?php

namespace App\Infrastructure\Eventing;

trait RecordsEvents
{
    /** @var DomainEvent[] */
    private array $recordedEvents = [];

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return DomainEvent[]
     */
    public function getRecordedEvents(): array
    {
        $recordedEvents = $this->recordedEvents;
        $this->recordedEvents = [];

        return $recordedEvents;
    }
}
