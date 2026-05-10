<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Strength;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Strength\DbalStrengthWorkoutRepository;
use App\Domain\Activity\Strength\ExerciseName;
use App\Domain\Activity\Strength\ExerciseSet;
use App\Domain\Activity\Strength\StrengthWorkoutExercises;
use App\Domain\Activity\Strength\StrengthWorkoutRepository;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use App\Tests\ContainerTestCase;

class DbalStrengthWorkoutRepositoryTest extends ContainerTestCase
{
    private StrengthWorkoutRepository $strengthWorkoutRepository;

    public function testSaveForActivityAndFindByActivityId(): void
    {
        $activityId = ActivityId::fromUnprefixed('test');
        $exercises = StrengthWorkoutExercises::fromArray([
            ExerciseSet::create(ExerciseName::fromString('Squat'), 3, 5, Pound::from(315.0)),
            ExerciseSet::create(ExerciseName::fromString('Pull Up'), 3, 10),
        ]);

        $this->strengthWorkoutRepository->saveForActivity($activityId, $exercises);

        $found = $this->strengthWorkoutRepository->findByActivityId($activityId);
        $this->assertCount(2, $found);

        $items = $found->toArray();
        $this->assertSame('Squat', (string) $items[0]->getExerciseName());
        $this->assertSame(3, $items[0]->getNumberOfSets());
        $this->assertSame(5, $items[0]->getNumberOfReps());
        $this->assertSame(315.0, $items[0]->getWeightLbs()->toFloat());

        $this->assertSame('Pull Up', (string) $items[1]->getExerciseName());
        $this->assertTrue($items[1]->isBodyweight());
    }

    public function testEstimatedOneRepMaxIsComputedOnSave(): void
    {
        $activityId = ActivityId::fromUnprefixed('test');
        $exercises = StrengthWorkoutExercises::fromArray([
            ExerciseSet::create(ExerciseName::fromString('Deadlift'), 1, 5, Pound::from(300.0)),
        ]);

        $this->strengthWorkoutRepository->saveForActivity($activityId, $exercises);

        $row = $this->getConnection()
            ->executeQuery('SELECT estimatedOneRepMax FROM ActivityStrengthSet WHERE activityId = :id ORDER BY position ASC', ['id' => $activityId])
            ->fetchOne();

        // Epley: 300 * (1 + 5/30) = 300 * 1.1667 = 350.0
        $this->assertEqualsWithDelta(300.0 * (1 + 5 / 30.0), (float) $row, 0.001);
    }

    public function testBodyweightSetHasNullOneRepMax(): void
    {
        $activityId = ActivityId::fromUnprefixed('test');
        $exercises = StrengthWorkoutExercises::fromArray([
            ExerciseSet::create(ExerciseName::fromString('Dip'), 3, 12),
        ]);

        $this->strengthWorkoutRepository->saveForActivity($activityId, $exercises);

        $row = $this->getConnection()
            ->executeQuery('SELECT estimatedOneRepMax FROM ActivityStrengthSet WHERE activityId = :id ORDER BY position ASC', ['id' => $activityId])
            ->fetchOne();

        $this->assertNull($row ?: null);
    }

    public function testIsImportedForActivity(): void
    {
        $activityId = ActivityId::fromUnprefixed('test');
        $exercises = StrengthWorkoutExercises::fromArray([
            ExerciseSet::create(ExerciseName::fromString('Bench Press'), 4, 8, Pound::from(185.0)),
        ]);

        $this->assertFalse($this->strengthWorkoutRepository->isImportedForActivity($activityId));

        $this->strengthWorkoutRepository->saveForActivity($activityId, $exercises);

        $this->assertTrue($this->strengthWorkoutRepository->isImportedForActivity($activityId));
        $this->assertFalse($this->strengthWorkoutRepository->isImportedForActivity(ActivityId::fromUnprefixed('other')));
    }

    public function testDeleteForActivity(): void
    {
        $activityIdOne = ActivityId::fromUnprefixed('test1');
        $activityIdTwo = ActivityId::fromUnprefixed('test2');
        $exercises = StrengthWorkoutExercises::fromArray([
            ExerciseSet::create(ExerciseName::fromString('Squat'), 3, 5, Pound::from(225.0)),
        ]);

        $this->strengthWorkoutRepository->saveForActivity($activityIdOne, $exercises);
        $this->strengthWorkoutRepository->saveForActivity($activityIdTwo, $exercises);

        $this->strengthWorkoutRepository->deleteForActivity($activityIdOne);

        $this->assertSame(
            1,
            (int) $this->getConnection()->executeQuery('SELECT COUNT(*) FROM ActivityStrengthSet')->fetchOne()
        );
        $this->assertFalse($this->strengthWorkoutRepository->isImportedForActivity($activityIdOne));
        $this->assertTrue($this->strengthWorkoutRepository->isImportedForActivity($activityIdTwo));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->strengthWorkoutRepository = new DbalStrengthWorkoutRepository($this->getConnection());
    }
}
