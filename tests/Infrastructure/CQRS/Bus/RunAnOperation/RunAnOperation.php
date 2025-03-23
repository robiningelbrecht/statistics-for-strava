<?php

namespace App\Tests\Infrastructure\CQRS\Bus\RunAnOperation;

use App\Infrastructure\CQRS\DomainCommand;

final readonly class RunAnOperation extends DomainCommand
{
    private readonly string $notInitialized;

    public function __construct(
        private ?string $value,
        private ?string $valueTwo = 'defaultValue',
    ) {
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getValueTwo(): ?string
    {
        return $this->valueTwo;
    }
}
