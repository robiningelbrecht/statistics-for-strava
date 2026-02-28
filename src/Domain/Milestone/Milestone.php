<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\MilestoneContext;
use App\Domain\Milestone\FunComparison\FunComparison;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Milestone
{
    private function __construct(
        private SerializableDateTime $achievedOn,
        private MilestoneCategory $category,
        private ?SportType $sportType,
        private ?ActivityId $activityId,
        private string $title,
        private MilestoneContext $context,
        private ?PreviousMilestone $previous,
        private ?FunComparison $funComparison,
    ) {
    }

    public static function create(
        SerializableDateTime $achievedOn,
        MilestoneCategory $category,
        ?SportType $sportType,
        ?ActivityId $activityId,
        string $title,
        MilestoneContext $context,
        ?PreviousMilestone $previous = null,
        ?FunComparison $funComparison = null,
    ): self {
        return new self(
            achievedOn: $achievedOn,
            category: $category,
            sportType: $sportType,
            activityId: $activityId,
            title: $title,
            context: $context,
            previous: $previous,
            funComparison: $funComparison,
        );
    }

    public function getAchievedOn(): SerializableDateTime
    {
        return $this->achievedOn;
    }

    public function getCategory(): MilestoneCategory
    {
        return $this->category;
    }

    public function getSportType(): ?SportType
    {
        return $this->sportType;
    }

    public function getActivityId(): ?ActivityId
    {
        return $this->activityId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContext(): MilestoneContext
    {
        return $this->context;
    }

    public function getPrevious(): ?PreviousMilestone
    {
        return $this->previous;
    }

    public function getFunComparison(): ?FunComparison
    {
        return $this->funComparison;
    }
}
