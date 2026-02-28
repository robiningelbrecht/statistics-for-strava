<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\MovingTimeFunComparison;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class MovingTimeFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (MovingTimeFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(MovingTimeFunComparison::resolve(Hour::from(1)));
        $this->assertNull(MovingTimeFunComparison::resolve(Hour::from(7)));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(float $hours, MovingTimeFunComparison $expected): void
    {
        $this->assertEquals($expected, MovingTimeFunComparison::resolve(Hour::from($hours)));
    }

    /**
     * @return \Generator<string, array{float, MovingTimeFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'work day' => [8, MovingTimeFunComparison::FULL_WORK_DAY];
        yield 'lotr' => [12, MovingTimeFunComparison::LOTR_EXTENDED];
        yield 'full day' => [24, MovingTimeFunComparison::FULL_DAY];
        yield 'two days' => [48, MovingTimeFunComparison::TWO_FULL_DAYS];
        yield 'three days' => [72, MovingTimeFunComparison::THREE_FULL_DAYS];
        yield 'four days' => [100, MovingTimeFunComparison::FOUR_DAYS];
        yield 'full week' => [168, MovingTimeFunComparison::FULL_WEEK];
        yield 'two weeks' => [336, MovingTimeFunComparison::TWO_WEEKS];
        yield 'three weeks' => [500, MovingTimeFunComparison::THREE_WEEKS];
        yield 'full month' => [744, MovingTimeFunComparison::FULL_MONTH];
        yield '41 days' => [1_000, MovingTimeFunComparison::FORTY_ONE_DAYS];
        yield 'two months' => [1_440, MovingTimeFunComparison::TWO_MONTHS];
        yield 'three months' => [2_160, MovingTimeFunComparison::THREE_MONTHS];
        yield 'six months' => [4_380, MovingTimeFunComparison::SIX_MONTHS];
        yield 'full year' => [8_760, MovingTimeFunComparison::FULL_YEAR];
    }
}
