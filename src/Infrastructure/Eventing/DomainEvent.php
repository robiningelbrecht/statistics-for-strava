<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @codeCoverageIgnore
 */
abstract class DomainEvent extends Event implements \JsonSerializable
{
    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'eventName' => str_replace('\\', '.', static::class),
            'payload' => $this->getSerializablePayload(),
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function getSerializablePayload(): array
    {
        $serializedPayload = [];
        foreach (new \ReflectionClass($this)->getProperties() as $property) {
            $serializedPayload[$property->getName()] = $property->getValue($this);
        }

        return $serializedPayload;
    }
}
