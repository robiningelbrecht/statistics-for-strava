<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class PreviousMilestone
{
    private function __construct(
        private MilestoneId $milestoneId,
        private string $label,
        private SerializableDateTime $achievedOn,
    ) {
    }

    public static function create(
        MilestoneId $milestoneId,
        string $label,
        SerializableDateTime $achievedOn,
    ): self {
        return new self(
            milestoneId: $milestoneId,
            label: $label,
            achievedOn: $achievedOn,
        );
    }

    public function getMilestoneId(): MilestoneId
    {
        return $this->milestoneId;
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
