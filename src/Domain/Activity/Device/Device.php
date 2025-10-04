<?php

declare(strict_types=1);

namespace App\Domain\Activity\Device;

use PHLAK\Twine\Str;

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
        return (string) Str::make($this->name)->kebabCase();
    }

    public function getName(): string
    {
        return $this->name;
    }
}
