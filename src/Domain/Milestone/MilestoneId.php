<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

final readonly class MilestoneId implements \Stringable, \JsonSerializable
{
    private function __construct(
        private string $id,
    ) {
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function jsonSerialize(): string
    {
        return $this->id;
    }
}
