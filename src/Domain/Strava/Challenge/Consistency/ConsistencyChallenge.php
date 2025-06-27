<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\SportType\SportTypes;

final readonly class ConsistencyChallenge
{
    private function __construct(
        private string $label,
        private bool $isEnabled,
        private ChallengeConsistencyType $type,
        private ChallengeConsistencyGoal $goal,
        private SportTypes $sportsTypesToInclude,
    ) {
    }

    public static function from(
        string $label,
        bool $isEnabled,
        ChallengeConsistencyType $type,
        ChallengeConsistencyGoal $goal,
        SportTypes $sportsTypesToInclude,
    ): self {
        return new self(
            label: $label,
            isEnabled: $isEnabled,
            type: $type,
            goal: $goal,
            sportsTypesToInclude: $sportsTypesToInclude,
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

    public function getSportsTypesToInclude(): SportTypes
    {
        return $this->sportsTypesToInclude;
    }
}
