<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Strength;

use App\Domain\Activity\Strength\StrengthWorkoutDescriptionParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StrengthWorkoutDescriptionParserTest extends TestCase
{
    private StrengthWorkoutDescriptionParser $parser;

    protected function setUp(): void
    {
        $this->parser = new StrengthWorkoutDescriptionParser();
    }

    #[DataProvider(methodName: 'provideValidSingleLineDescriptions')]
    public function testParseSingleLine(
        string $description,
        string $expectedName,
        int $expectedSets,
        int $expectedReps,
        ?float $expectedWeightLbs,
    ): void {
        $exercises = $this->parser->parse($description);

        $this->assertCount(1, $exercises);
        $first = $exercises->getFirst();
        $this->assertEquals($expectedName, (string) $first->getExerciseName());
        $this->assertEquals($expectedSets, $first->getNumberOfSets());
        $this->assertEquals($expectedReps, $first->getNumberOfReps());
        if (null === $expectedWeightLbs) {
            $this->assertNull($first->getWeightLbs());
        } else {
            $this->assertEquals($expectedWeightLbs, $first->getWeightLbs()->toFloat());
        }
    }

    public static function provideValidSingleLineDescriptions(): iterable
    {
        yield 'simple exercise with integer weight' => ['Squat 3x5@100', 'Squat', 3, 5, 100.0];
        yield 'multi-word name with weight' => ['Bench Press 4x8@80', 'Bench Press', 4, 8, 80.0];
        yield 'float weight' => ['Deadlift 1x3@140.5', 'Deadlift', 1, 3, 140.5];
        yield 'bodyweight exercise (no @weight)' => ['Pull Up 3x10', 'Pull Up', 3, 10, null];
        yield 'three-word name' => ['Romanian Dead Lift 3x8@60', 'Romanian Dead Lift', 3, 8, 60.0];
        yield 'leading whitespace trimmed' => ['  Squat 3x5@100', 'Squat', 3, 5, 100.0];
        yield 'trailing whitespace trimmed' => ['Squat 3x5@100  ', 'Squat', 3, 5, 100.0];
    }

    #[DataProvider(methodName: 'provideDescriptionsWithNoExercises')]
    public function testParseReturnsEmptyCollection(string $description): void
    {
        $exercises = $this->parser->parse($description);

        $this->assertTrue($exercises->isEmpty());
    }

    public static function provideDescriptionsWithNoExercises(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'prose only' => ['Rest day, feeling good'];
        yield 'name starts with digit' => ['3x5@100'];
        yield 'zero sets rejected by regex' => ['Squat 0x5@100'];
        yield 'zero reps rejected by regex' => ['Squat 3x0@100'];
        yield 'trailing @ with no weight' => ['Squat 3x5@'];
        yield 'missing sets token' => ['Squat x5@100'];
        yield 'missing reps token' => ['Squat 3x@100'];
    }

    public function testParseMultiLineCanonicalExample(): void
    {
        $description = implode("\n", [
            'Squat 3x5@100',
            'Bench Press 4x8@80',
            'Deadlift 1x3@140',
            'Pull Up 3x10',
        ]);

        $exercises = $this->parser->parse($description);
        $this->assertCount(4, $exercises);

        $items = $exercises->toArray();
        $this->assertEquals('Squat', (string) $items[0]->getExerciseName());
        $this->assertEquals(3, $items[0]->getNumberOfSets());
        $this->assertEquals(5, $items[0]->getNumberOfReps());
        $this->assertEquals(100.0, $items[0]->getWeightLbs()->toFloat());

        $this->assertEquals('Bench Press', (string) $items[1]->getExerciseName());
        $this->assertEquals(4, $items[1]->getNumberOfSets());
        $this->assertEquals(8, $items[1]->getNumberOfReps());
        $this->assertEquals(80.0, $items[1]->getWeightLbs()->toFloat());

        $this->assertEquals('Deadlift', (string) $items[2]->getExerciseName());
        $this->assertEquals(1, $items[2]->getNumberOfSets());
        $this->assertEquals(3, $items[2]->getNumberOfReps());
        $this->assertEquals(140.0, $items[2]->getWeightLbs()->toFloat());

        $this->assertEquals('Pull Up', (string) $items[3]->getExerciseName());
        $this->assertTrue($items[3]->isBodyweight());
    }

    public function testParseMultiLineWithMixedContent(): void
    {
        $description = implode("\n", [
            'Good session today!',
            'Squat 3x5@100',
            'Felt strong.',
            'Bench Press 4x8@80',
            'Pull Up 3x10',
            'Cool down stretches done.',
        ]);

        $exercises = $this->parser->parse($description);
        $this->assertCount(3, $exercises);

        $items = $exercises->toArray();
        $this->assertEquals('Squat', (string) $items[0]->getExerciseName());
        $this->assertEquals('Bench Press', (string) $items[1]->getExerciseName());
        $this->assertEquals('Pull Up', (string) $items[2]->getExerciseName());
    }

    public function testParseCrlfLineEndings(): void
    {
        $description = "Squat 3x5@100\r\nBench Press 4x8@80\r\nPull Up 3x10";

        $exercises = $this->parser->parse($description);
        $this->assertCount(3, $exercises);
    }
}
