<?php

declare(strict_types=1);

namespace App\Domain\Challenge\Consistency;

use App\Domain\Activity\SportType\SportTypes;

final readonly class ConsistencyChallenge
{
    private function __construct(
        private string $label,
        private bool $isEnabled,
        private ChallengeConsistencyType $type,
        private ChallengeConsistencyGoal $goal,
        private SportTypes $sportTypesToInclude,
    ) {
    }

    public static function create(
        string $label,
        bool $isEnabled,
        ChallengeConsistencyType $type,
        ChallengeConsistencyGoal $goal,
        SportTypes $sportTypesToInclude,
    ): self {
        return new self(
            label: $label,
            isEnabled: $isEnabled,
            type: $type,
            goal: $goal,
            sportTypesToInclude: $sportTypesToInclude,
        );
    }

    public function getId(): string
    {
        /** @var string $sanitizedLabel */
        $sanitizedLabel = preg_replace('/-+/', '-', str_replace(' ', '-',
            preg_replace('/[^a-z0-9 ]/', '', strtolower($this->label)) // @phpstan-ignore argument.type
        ));

        return $sanitizedLabel;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getType(): ChallengeConsistencyType
    {
        return $this->type;
    }

    public function getGoal(): ChallengeConsistencyGoal
    {
        return $this->goal;
    }

    public function getSportTypesToInclude(): SportTypes
    {
        return $this->sportTypesToInclude;
    }
}
