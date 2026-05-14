<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Gap;

use App\Domain\Activity\Gap\StravaLikeGapAdjustmentModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StravaLikeGapAdjustmentModelTest extends TestCase
{
    #[DataProvider('knownGradeFactorProvider')]
    public function testExactKnownGradesReturnDocumentedFactors(float $grade, float $expectedFactor): void
    {
        self::assertEqualsWithDelta(
            $expectedFactor,
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor($grade),
            0.0001,
        );
    }

    /**
     * @return \Generator<string, array{float, float}>
     */
    public static function knownGradeFactorProvider(): \Generator
    {
        yield 'grade -0.50' => [-0.50, 1.35];
        yield 'grade -0.35' => [-0.35, 1.25];
        yield 'grade -0.25' => [-0.25, 1.12];
        yield 'grade -0.18' => [-0.18, 1.01];
        yield 'grade -0.15' => [-0.15, 0.96];
        yield 'grade -0.10' => [-0.10, 0.88];
        yield 'grade -0.05' => [-0.05, 0.94];
        yield 'grade  0.00' => [0.00, 1.00];
        yield 'grade  0.05' => [0.05, 1.20];
        yield 'grade  0.10' => [0.10, 1.42];
        yield 'grade  0.15' => [0.15, 1.70];
        yield 'grade  0.20' => [0.20, 2.00];
        yield 'grade  0.30' => [0.30, 2.60];
        yield 'grade  0.40' => [0.40, 3.25];
        yield 'grade  0.50' => [0.50, 3.90];
        yield 'interpolated 0.025' => [0.025, 1.10];
    }

    public function testClampsGradeAboveUpperBound(): void
    {
        self::assertEqualsWithDelta(
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(0.50),
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(2.0),
            0.0001,
        );
    }

    public function testClampsGradeBelowLowerBound(): void
    {
        self::assertEqualsWithDelta(
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(-0.50),
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(-2.0),
            0.0001,
        );
    }

    #[DataProvider('uphillMonotonicProvider')]
    public function testUphillFactorsIncreaseMonotonically(float $lowerGrade, float $higherGrade): void
    {
        self::assertGreaterThan(
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor($lowerGrade),
            StravaLikeGapAdjustmentModel::calculateAdjustmentFactor($higherGrade),
        );
    }

    /**
     * @return \Generator<string, array{float, float}>
     */
    public static function uphillMonotonicProvider(): \Generator
    {
        $grades = [0.00, 0.05, 0.10, 0.15, 0.20, 0.30, 0.40, 0.50];

        for ($i = 1; $i < count($grades); ++$i) {
            yield sprintf('%.2f < %.2f', $grades[$i - 1], $grades[$i]) => [$grades[$i - 1], $grades[$i]];
        }
    }

    #[DataProvider('steepDownhillProvider')]
    public function testSteepDownhillReboundsAboveFlat(float $grade): void
    {
        $flat = StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(0.0);

        self::assertGreaterThan($flat, StravaLikeGapAdjustmentModel::calculateAdjustmentFactor($grade));
    }

    /**
     * @return \Generator<string, array{float}>
     */
    public static function steepDownhillProvider(): \Generator
    {
        yield 'grade -0.50' => [-0.50];
        yield 'grade -0.35' => [-0.35];
        yield 'grade -0.25' => [-0.25];
    }

    #[DataProvider('mildDownhillProvider')]
    public function testMildDownhillDipsBelowFlat(float $grade): void
    {
        $flat = StravaLikeGapAdjustmentModel::calculateAdjustmentFactor(0.0);

        self::assertLessThan($flat, StravaLikeGapAdjustmentModel::calculateAdjustmentFactor($grade));
    }

    /**
     * @return \Generator<string, array{float}>
     */
    public static function mildDownhillProvider(): \Generator
    {
        yield 'grade -0.10' => [-0.10];
        yield 'grade -0.05' => [-0.05];
    }
}
