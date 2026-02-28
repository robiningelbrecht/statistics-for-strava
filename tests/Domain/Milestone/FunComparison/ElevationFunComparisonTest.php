<?php

namespace App\Tests\Domain\Milestone\FunComparison;

use App\Domain\Milestone\FunComparison\ElevationFunComparison;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class ElevationFunComparisonTest extends TestCase
{
    public function testTransReturnsNonEmptyStringForAllCases(): void
    {
        $translator = new IdentityTranslator();

        foreach (ElevationFunComparison::cases() as $case) {
            $this->assertNotEmpty($case->trans($translator));
        }
    }

    public function testResolveReturnsNullBelowMinimum(): void
    {
        $this->assertNull(ElevationFunComparison::resolve(Meter::from(100)));
        $this->assertNull(ElevationFunComparison::resolve(Meter::from(299)));
    }

    #[DataProvider(methodName: 'resolveProvider')]
    public function testResolve(float $meters, ElevationFunComparison $expected): void
    {
        $this->assertEquals($expected, ElevationFunComparison::resolve(Meter::from($meters)));
    }

    /**
     * @return \Generator<string, array{float, ElevationFunComparison}>
     */
    public static function resolveProvider(): \Generator
    {
        yield 'eiffel tower' => [300, ElevationFunComparison::EIFFEL_TOWER];
        yield 'empire state' => [500, ElevationFunComparison::EMPIRE_STATE_BUILDING];
        yield 'two eiffels' => [1_000, ElevationFunComparison::TWO_EIFFEL_TOWERS];
        yield 'alpe d huez' => [2_469, ElevationFunComparison::ALPE_D_HUEZ];
        yield 'mount fuji' => [3_776, ElevationFunComparison::MOUNT_FUJI];
        yield 'mont blanc' => [4_808, ElevationFunComparison::MONT_BLANC];
        yield 'denali' => [6_190, ElevationFunComparison::DENALI];
        yield 'everest' => [8_849, ElevationFunComparison::MOUNT_EVEREST];
        yield 'cruising altitude' => [12_000, ElevationFunComparison::CRUISING_ALTITUDE];
        yield 'everest twice' => [17_772, ElevationFunComparison::EVEREST_TWICE];
        yield 'everest 3x' => [26_658, ElevationFunComparison::EVEREST_THREE_TIMES];
        yield 'baumgartner' => [39_000, ElevationFunComparison::BAUMGARTNER_SPACE_JUMP];
        yield 'everest 6x' => [53_069, ElevationFunComparison::EVEREST_SIX_TIMES];
        yield 'everest 10x' => [88_448, ElevationFunComparison::EVEREST_TEN_TIMES];
        yield 'karman line' => [100_000, ElevationFunComparison::KARMAN_LINE];
        yield 'halfway iss' => [200_000, ElevationFunComparison::HALFWAY_TO_ISS];
        yield 'iss altitude' => [408_000, ElevationFunComparison::ISS_ALTITUDE];
        yield 'higher than iss' => [500_000, ElevationFunComparison::HIGHER_THAN_ISS];
        yield 'edge of space 5x' => [1_000_000, ElevationFunComparison::EDGE_OF_SPACE_FIVE_TIMES];
    }
}
