<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\StreakFunComparison;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class StreakFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (StreakFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(StreakFunComparison::resolve(1));
        $this->assertNull(StreakFunComparison::resolve(6));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(int $days, StreakFunComparison $expected): void
    {
        $this->assertEquals($expected, StreakFunComparison::resolve($days));
    }

    /**
     * @return \Generator<string, array{int, StreakFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'full week' => [7, StreakFunComparison::FULL_WEEK];
        yield 'fortnight' => [14, StreakFunComparison::FORTNIGHT];
        yield '21 days' => [21, StreakFunComparison::TWENTY_ONE_DAYS_HABIT];
        yield 'full month' => [30, StreakFunComparison::FULL_MONTH];
        yield 'two months' => [60, StreakFunComparison::TWO_MONTHS];
        yield 'full quarter' => [90, StreakFunComparison::FULL_QUARTER];
        yield '100 days' => [100, StreakFunComparison::HUNDRED_DAYS];
        yield 'half year' => [180, StreakFunComparison::HALF_YEAR];
        yield 'full year' => [365, StreakFunComparison::FULL_YEAR];
    }
}
