<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use App\Infrastructure\ValueObject\Measurement\Mass\Pound;

final class StrengthWorkoutDescriptionParser
{
    // Format: [lift_name] [sets]x[reps]  or  [lift_name] [sets]x[reps]@[weight]
    // Name must start with a letter; sets/reps must be positive (no zero, no leading zeros).
    // The lookahead (?=\d+x\d+) anchors the boundary between name and the numeric token,
    // allowing multi-word names like "Bench Press" without ambiguity.
    private const string LINE_PATTERN =
        '/^(?P<name>[A-Za-z][A-Za-z0-9 ]*?)\s+(?=\d+x\d+)(?P<sets>[1-9]\d*)x(?P<reps>[1-9]\d*)(?:@(?P<weight>\d+(?:\.\d+)?))?$/';

    public function parse(string $description): StrengthWorkoutExercises
    {
        $exercises = StrengthWorkoutExercises::empty();

        if ('' === trim($description)) {
            return $exercises;
        }

        foreach (preg_split('/\r?\n/', $description) ?: [] as $line) {
            $trimmed = trim($line);
            if ('' === $trimmed) {
                continue;
            }
            if (!preg_match(self::LINE_PATTERN, $trimmed, $matches)) {
                continue;
            }

            $exercises->add(ExerciseSet::create(
                exerciseName: ExerciseName::fromString($matches['name']),
                numberOfSets: (int) $matches['sets'],
                numberOfReps: (int) $matches['reps'],
                weightLbs: isset($matches['weight'])
                    ? Pound::from((float) $matches['weight'])
                    : null,
            ));
        }

        return $exercises;
    }
}
