<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command;

abstract readonly class DomainCommand implements Command
{
    /**
     * @return array{commandName: string, payload: array<mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'commandName' => static::class,
            'payload' => $this->getSerializablePayload(),
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function getSerializablePayload(): array
    {
        $reflection = new \ReflectionClass($this);
        $serializedPayload = [];

        $properties = $reflection->getProperties();
        if ($parent = $reflection->getParentClass()) {
            $properties = [...$properties, ...$parent->getProperties()];
        }

        foreach ($properties as $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }
            $serializedPayload[$property->getName()] = $property->getValue($this);
        }

        return $serializedPayload;
    }
}
