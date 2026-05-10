<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use App\Domain\Activity\ActivityId;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;

final readonly class DbalStrengthWorkoutRepository extends DbalRepository implements StrengthWorkoutRepository
{
    public function saveForActivity(ActivityId $activityId, StrengthWorkoutExercises $exercises): void
    {
        $this->deleteForActivity($activityId);

        $sql = 'INSERT INTO ActivityStrengthSet (
            activityId, position, exerciseName, numberOfSets, numberOfReps, weightLbs, estimatedOneRepMax
        ) VALUES (
            :activityId, :position, :exerciseName, :numberOfSets, :numberOfReps, :weightLbs, :estimatedOneRepMax
        )';

        $position = 1;
        foreach ($exercises as $set) {
            /** @var ExerciseSet $set */
            $weightLbs = $set->getWeightLbs()?->toFloat();
            $estimatedOneRepMax = null !== $weightLbs
                ? $weightLbs * (1 + $set->getNumberOfReps() / 30.0)
                : null;

            $this->connection->executeStatement($sql, [
                'activityId' => $activityId,
                'position' => $position++,
                'exerciseName' => (string) $set->getExerciseName(),
                'numberOfSets' => $set->getNumberOfSets(),
                'numberOfReps' => $set->getNumberOfReps(),
                'weightLbs' => $weightLbs,
                'estimatedOneRepMax' => $estimatedOneRepMax,
            ]);
        }
    }

    public function findByActivityId(ActivityId $activityId): StrengthWorkoutExercises
    {
        $results = $this->connection->executeQuery(
            'SELECT * FROM ActivityStrengthSet WHERE activityId = :activityId ORDER BY position ASC',
            ['activityId' => $activityId],
        )->fetchAllAssociative();

        return StrengthWorkoutExercises::fromArray(array_map($this->hydrate(...), $results));
    }

    public function isImportedForActivity(ActivityId $activityId): bool
    {
        return $this->connection->executeQuery(
            'SELECT COUNT(*) FROM ActivityStrengthSet WHERE activityId = :activityId',
            ['activityId' => $activityId],
        )->fetchOne() > 0;
    }

    public function deleteForActivity(ActivityId $activityId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ActivityStrengthSet WHERE activityId = :activityId',
            ['activityId' => $activityId],
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ExerciseSet
    {
        return ExerciseSet::create(
            exerciseName: ExerciseName::fromString($result['exerciseName']),
            numberOfSets: (int) $result['numberOfSets'],
            numberOfReps: (int) $result['numberOfReps'],
            weightLbs: null !== $result['weightLbs'] ? Pound::from((float) $result['weightLbs']) : null,
        );
    }
}
