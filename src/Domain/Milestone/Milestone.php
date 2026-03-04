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
        private MilestoneId $id,
        private SerializableDateTime $achievedOn,
        private MilestoneCategory $category,
        private ?SportType $sportType,
        private ?ActivityId $activityId,
        private MilestoneContext $context,
        private ?PreviousMilestone $previous,
        private ?FunComparison $funComparison,
    ) {
    }

    public static function create(
        MilestoneId $id,
        SerializableDateTime $achievedOn,
        MilestoneCategory $category,
        MilestoneContext $context,
    ): self {
        return new self(
            id: $id,
            achievedOn: $achievedOn,
            category: $category,
            sportType: null,
            activityId: null,
            context: $context,
            previous: null,
            funComparison: null,
        );
    }

    public function getId(): MilestoneId
    {
        return $this->id;
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

    public function withSportType(?SportType $sportType): self
    {
        return clone ($this, [
            'sportType' => $sportType,
        ]);
    }

    public function getActivityId(): ?ActivityId
    {
        return $this->activityId;
    }

    public function withActivityId(ActivityId $activityId): self
    {
        return clone ($this, [
            'activityId' => $activityId,
        ]);
    }

    public function getContext(): MilestoneContext
    {
        return $this->context;
    }

    public function getPrevious(): ?PreviousMilestone
    {
        return $this->previous;
    }

    public function withPrevious(?PreviousMilestone $previous): self
    {
        return clone ($this, [
            'previous' => $previous,
        ]);
    }

    public function getFunComparison(): ?FunComparison
    {
        return $this->funComparison;
    }

    public function withFunComparison(?FunComparison $funComparison): self
    {
        return clone ($this, [
            'funComparison' => $funComparison,
        ]);
    }
}
