<?php

declare(strict_types=1);

namespace App\Domain\Activity\Device;

use App\Infrastructure\ValueObject\String\SanitizedString;

final readonly class Device
{
    private function __construct(
        private string $name,
    ) {
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function getId(): string
    {
        return (string) SanitizedString::fromString($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
