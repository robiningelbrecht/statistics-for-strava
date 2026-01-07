<?php

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ActivitySummary
{
    private function __construct(
        private string $name,
        private SerializableDateTime $startDateTime,
    ) {
    }

    public static function create(
        string $name,
        SerializableDateTime $startDateTime,
    ): self {
        return new self(
            name: $name,
            startDateTime: $startDateTime,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDateTime;
    }
}
