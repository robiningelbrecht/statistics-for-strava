<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

final readonly class MilestoneId implements \Stringable
{
    private function __construct(
        private string $id,
    ) {
    }

    public static function fromParts(string ...$parts): self
    {
        return new self(implode('-', ['milestone', ...$parts]));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
