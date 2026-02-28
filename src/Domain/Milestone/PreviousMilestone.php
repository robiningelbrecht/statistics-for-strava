<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class PreviousMilestone
{
    private function __construct(
        private string $label,
        private SerializableDateTime $achievedOn,
    ) {
    }

    public static function create(
        string $label,
        SerializableDateTime $achievedOn,
    ): self {
        return new self(
            label: $label,
            achievedOn: $achievedOn,
        );
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAchievedOn(): SerializableDateTime
    {
        return $this->achievedOn;
    }
}
