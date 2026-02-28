<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\EddingtonFunComparison;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class EddingtonFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (EddingtonFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(EddingtonFunComparison::resolve(1));
        $this->assertNull(EddingtonFunComparison::resolve(24));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(int $number, EddingtonFunComparison $expected): void
    {
        $this->assertEquals($expected, EddingtonFunComparison::resolve($number));
    }

    /**
     * @return \Generator<string, array{int, EddingtonFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'building consistency' => [25, EddingtonFunComparison::BUILDING_CONSISTENCY];
        yield 'impressive dedication' => [50, EddingtonFunComparison::IMPRESSIVE_DEDICATION];
        yield 'exponentially tougher' => [75, EddingtonFunComparison::EXPONENTIALLY_TOUGHER];
        yield 'true centurion' => [100, EddingtonFunComparison::TRUE_CENTURION];
        yield 'elite territory' => [150, EddingtonFunComparison::ELITE_TERRITORY];
        yield 'legendary endurance' => [200, EddingtonFunComparison::LEGENDARY_ENDURANCE];
    }
}
