<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ActivitySummary
{
    private function __construct(
        private string $name,
        private SerializableDateTime $startDateTime,
        private SportType $sportType,
    ) {
    }

    public static function create(
        string $name,
        SerializableDateTime $startDateTime,
        SportType $sportType,
    ): self {
        return new self(
            name: $name,
            startDateTime: $startDateTime,
            sportType: $sportType,
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

    public function getSportType(): SportType
    {
        return $this->sportType;
    }
}
