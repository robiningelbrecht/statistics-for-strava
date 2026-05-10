<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use App\Infrastructure\ValueObject\Measurement\Mass\Pound;

final readonly class ExerciseSet implements \JsonSerializable
{
    private function __construct(
        private ExerciseName $exerciseName,
        private int $numberOfSets,
        private int $numberOfReps,
        private ?Pound $weightLbs,
    ) {
        if ($this->numberOfSets <= 0) {
            throw new \InvalidArgumentException(sprintf('Number of sets must be a positive integer, got: %d', $this->numberOfSets));
        }
        if ($this->numberOfReps <= 0) {
            throw new \InvalidArgumentException(sprintf('Number of reps must be a positive integer, got: %d', $this->numberOfReps));
        }
    }

    public static function create(
        ExerciseName $exerciseName,
        int $numberOfSets,
        int $numberOfReps,
        ?Pound $weightLbs = null,
    ): self {
        return new self(
            exerciseName: $exerciseName,
            numberOfSets: $numberOfSets,
            numberOfReps: $numberOfReps,
            weightLbs: $weightLbs,
        );
    }

    public function getExerciseName(): ExerciseName
    {
        return $this->exerciseName;
    }

    public function getNumberOfSets(): int
    {
        return $this->numberOfSets;
    }

    public function getNumberOfReps(): int
    {
        return $this->numberOfReps;
    }

    public function getWeightLbs(): ?Pound
    {
        return $this->weightLbs;
    }

    public function isBodyweight(): bool
    {
        return !$this->weightLbs instanceof \App\Infrastructure\ValueObject\Measurement\Mass\Pound;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'exerciseName' => $this->exerciseName,
            'numberOfSets' => $this->numberOfSets,
            'numberOfReps' => $this->numberOfReps,
            'weightLbs' => $this->weightLbs,
        ];
    }
}
