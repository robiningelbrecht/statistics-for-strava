<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Strength;

use App\Domain\Activity\Strength\ExerciseName;
use App\Domain\Activity\Strength\ExerciseSet;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use PHPUnit\Framework\TestCase;

class ExerciseSetTest extends TestCase
{
    public function testCreateWithWeight(): void
    {
        $set = ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Squat'),
            numberOfSets: 3,
            numberOfReps: 5,
            weightLbs: Pound::from(100.0),
        );

        $this->assertEquals('Squat', (string) $set->getExerciseName());
        $this->assertEquals(3, $set->getNumberOfSets());
        $this->assertEquals(5, $set->getNumberOfReps());
        $this->assertEquals(100.0, $set->getWeightLbs()->toFloat());
        $this->assertFalse($set->isBodyweight());
    }

    public function testCreateBodyweightExercise(): void
    {
        $set = ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Pull Up'),
            numberOfSets: 3,
            numberOfReps: 10,
        );

        $this->assertNull($set->getWeightLbs());
        $this->assertTrue($set->isBodyweight());
    }

    public function testJsonSerializeWithWeight(): void
    {
        $set = ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Bench Press'),
            numberOfSets: 4,
            numberOfReps: 8,
            weightLbs: Pound::from(80.0),
        );

        $data = $set->jsonSerialize();

        $this->assertSame('Bench Press', (string) $data['exerciseName']);
        $this->assertSame(4, $data['numberOfSets']);
        $this->assertSame(8, $data['numberOfReps']);
        $this->assertSame(80.0, $data['weightLbs']->toFloat());
    }

    public function testJsonSerializeBodyweight(): void
    {
        $set = ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Pull Up'),
            numberOfSets: 3,
            numberOfReps: 10,
        );

        $data = $set->jsonSerialize();

        $this->assertNull($data['weightLbs']);
    }

    public function testItShouldThrowOnZeroSets(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Squat'),
            numberOfSets: 0,
            numberOfReps: 5,
        );
    }

    public function testItShouldThrowOnNegativeSets(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Squat'),
            numberOfSets: -1,
            numberOfReps: 5,
        );
    }

    public function testItShouldThrowOnZeroReps(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ExerciseSet::create(
            exerciseName: ExerciseName::fromString('Squat'),
            numberOfSets: 3,
            numberOfReps: 0,
        );
    }
}
