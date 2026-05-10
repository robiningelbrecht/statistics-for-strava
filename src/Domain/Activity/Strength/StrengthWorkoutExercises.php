<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ExerciseSet>
 */
final class StrengthWorkoutExercises extends Collection
{
    public function getItemClassName(): string
    {
        return ExerciseSet::class;
    }
}
