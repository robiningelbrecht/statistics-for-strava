<?php

namespace App\Tests\Domain\Strava\Athlete\MaxHeartRate;

use App\Domain\Strava\Athlete\MaxHeartRate\Arena;
use App\Domain\Strava\Athlete\MaxHeartRate\Astrand;
use App\Domain\Strava\Athlete\MaxHeartRate\DateRangeBased;
use App\Domain\Strava\Athlete\MaxHeartRate\Fox;
use App\Domain\Strava\Athlete\MaxHeartRate\Gellish;
use App\Domain\Strava\Athlete\MaxHeartRate\InvalidMaxHeartRateFormula;
use App\Domain\Strava\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Strava\Athlete\MaxHeartRate\MaxHeartRateFormulas;
use App\Domain\Strava\Athlete\MaxHeartRate\Nes;
use App\Domain\Strava\Athlete\MaxHeartRate\Tanaka;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MaxHeartRateFormulasTest extends TestCase
{
    #[DataProvider(methodName: 'provideDetermineFormulaData')]
    public function testDetermineFormula(MaxHeartRateFormula $expectedFormula, string $formula): void
    {
        $this->assertEquals(
            $expectedFormula,
            new MaxHeartRateFormulas()->determineFormula($formula)
        );
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaIsEmpty(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA cannot be empty'));
        new MaxHeartRateFormulas()->determineFormula(' ');
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaIsNonExistent(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('Invalid MAX_HEART_RATE_FORMULA "invalid" detected'));
        new MaxHeartRateFormulas()->determineFormula('invalid');
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaIsInvalidJson(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('Invalid MAX_HEART_RATE_FORMULA "{lala" detected'));
        new MaxHeartRateFormulas()->determineFormula('{lala');
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaJsonIsNotAnArray(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA invalid date range'));
        new MaxHeartRateFormulas()->determineFormula('"a string"');
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaJsonContainsInvalidDates(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('Invalid date "lol" set in MAX_HEART_RATE_FORMULA'));
        new MaxHeartRateFormulas()->determineFormula('{"lol": 200}');
    }

    public function testItShouldThrowWhenMaxHeartRateFormulaJsonIsEmpty(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA date range cannot be empty'));
        new MaxHeartRateFormulas()->determineFormula('{}');
    }

    public static function provideDetermineFormulaData(): array
    {
        return [
            [new Arena(), 'arena'],
            [new Astrand(), 'astrand'],
            [
                DateRangeBased::empty()->addRange(
                    on: SerializableDateTime::fromString('2025-01-01'),
                    maxHeartRate: 100
                ),
                '{"2025-01-01": 100}',
            ],
            [new Fox(), 'fox'],
            [new Gellish(), 'gellish'],
            [new Nes(), 'nes'],
            [new Tanaka(), 'tanaka'],
        ];
    }
}
