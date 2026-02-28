<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\ActivityCountFunComparison;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class ActivityCountFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (ActivityCountFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(ActivityCountFunComparison::resolve(10));
        $this->assertNull(ActivityCountFunComparison::resolve(49));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(int $count, ActivityCountFunComparison $expected): void
    {
        $this->assertEquals($expected, ActivityCountFunComparison::resolve($count));
    }

    /**
     * @return \Generator<string, array{int, ActivityCountFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield '50' => [50, ActivityCountFunComparison::WEEKLY_FOR_YEAR];
        yield '100' => [100, ActivityCountFunComparison::TWICE_WEEKLY_FOR_YEAR];
        yield '250' => [250, ActivityCountFunComparison::FIVE_WEEKLY_FOR_YEAR];
        yield '365' => [365, ActivityCountFunComparison::DAILY_FOR_YEAR];
        yield '500' => [500, ActivityCountFunComparison::TWICE_WEEKLY_FOR_FIVE_YEARS];
        yield '750' => [750, ActivityCountFunComparison::THREE_WEEKLY_FOR_FIVE_YEARS];
        yield '1000' => [1_000, ActivityCountFunComparison::THREE_WEEKLY_FOR_SEVEN_YEARS];
        yield '1500' => [1_500, ActivityCountFunComparison::FOUR_WEEKLY_FOR_SEVEN_YEARS];
        yield '2000' => [2_000, ActivityCountFunComparison::FIVE_WEEKLY_FOR_SEVEN_YEARS];
        yield '2500' => [2_500, ActivityCountFunComparison::DAILY_FOR_SEVEN_YEARS];
        yield '5000' => [5_000, ActivityCountFunComparison::DAILY_FOR_FOURTEEN_YEARS];
        yield '10000' => [10_000, ActivityCountFunComparison::DAILY_FOR_TWENTYSEVEN_YEARS];
    }
}
