<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use Ramsey\Uuid\Uuid as RamseyUuid;

final readonly class MilestoneId implements \Stringable
{
    private function __construct(
        private string $id,
    ) {
    }

    public static function random(): self
    {
        return new self(implode('-', ['milestone', RamseyUuid::uuid4()->toString()]));
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
