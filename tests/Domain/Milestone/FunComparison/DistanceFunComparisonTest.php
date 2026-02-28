<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\DistanceFunComparison;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class DistanceFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (DistanceFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(DistanceFunComparison::resolve(Kilometer::from(50)));
        $this->assertNull(DistanceFunComparison::resolve(Kilometer::from(99)));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(float $km, DistanceFunComparison $expected): void
    {
        $this->assertEquals($expected, DistanceFunComparison::resolve(Kilometer::from($km)));
    }

    /**
     * @return \Generator<string, array{float, DistanceFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'century ride' => [100, DistanceFunComparison::CENTURY_RIDE];
        yield 'netherlands' => [300, DistanceFunComparison::NETHERLANDS_LENGTH];
        yield 'amsterdam-berlin' => [600, DistanceFunComparison::AMSTERDAM_BERLIN_RETURN];
        yield 'france' => [1_000, DistanceFunComparison::FRANCE_LENGTH];
        yield 'great britain' => [1_400, DistanceFunComparison::GREAT_BRITAIN_LENGTH];
        yield 'rhine' => [2_300, DistanceFunComparison::RHINE_RIVER];
        yield 'australia' => [3_500, DistanceFunComparison::WIDER_THAN_AUSTRALIA];
        yield 'great wall' => [5_000, DistanceFunComparison::GREAT_WALL_OF_CHINA];
        yield 'usa coast' => [6_671, DistanceFunComparison::USA_COAST_TO_COAST];
        yield 'trans siberian' => [9_288, DistanceFunComparison::TRANS_SIBERIAN_RAILWAY];
        yield 'earth diameter' => [12_742, DistanceFunComparison::EARTH_DIAMETER];
        yield 'halfway earth' => [20_038, DistanceFunComparison::HALFWAY_AROUND_EARTH];
        yield 'earth circumference' => [40_075, DistanceFunComparison::EARTH_CIRCUMFERENCE];
        yield 'twice earth' => [80_000, DistanceFunComparison::TWICE_AROUND_EARTH];
        yield '2.5x earth' => [100_000, DistanceFunComparison::TWO_AND_HALF_TIMES_AROUND_EARTH];
        yield 'moon' => [384_400, DistanceFunComparison::EARTH_TO_MOON];
    }
}
