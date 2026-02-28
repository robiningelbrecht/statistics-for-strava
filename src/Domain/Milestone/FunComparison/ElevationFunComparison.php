<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ElevationFunComparison: string implements FunComparison
{
    case EIFFEL_TOWER = 'elevationEiffelTower';
    case EMPIRE_STATE_BUILDING = 'elevationEmpireStateBuilding';
    case TWO_EIFFEL_TOWERS = 'elevationTwoEiffelTowers';
    case ALPE_D_HUEZ = 'elevationAlpeDHuez';
    case MOUNT_FUJI = 'elevationMountFuji';
    case MONT_BLANC = 'elevationMontBlanc';
    case DENALI = 'elevationDenali';
    case MOUNT_EVEREST = 'elevationMountEverest';
    case CRUISING_ALTITUDE = 'elevationCruisingAltitude';
    case EVEREST_TWICE = 'elevationEverestTwice';
    case EVEREST_THREE_TIMES = 'elevationEverestThreeTimes';
    case BAUMGARTNER_SPACE_JUMP = 'elevationBaumgartnerSpaceJump';
    case EVEREST_SIX_TIMES = 'elevationEverestSixTimes';
    case EVEREST_TEN_TIMES = 'elevationEverestTenTimes';
    case KARMAN_LINE = 'elevationKarmanLine';
    case HALFWAY_TO_ISS = 'elevationHalfwayToIss';
    case ISS_ALTITUDE = 'elevationIssAltitude';
    case HIGHER_THAN_ISS = 'elevationHigherThanIss';
    case EDGE_OF_SPACE_FIVE_TIMES = 'elevationEdgeOfSpaceFiveTimes';

    public static function resolve(Meter $elevation): ?self
    {
        $m = $elevation->toFloat();

        return match (true) {
            $m >= 1_000_000 => self::EDGE_OF_SPACE_FIVE_TIMES,
            $m >= 500_000 => self::HIGHER_THAN_ISS,
            $m >= 408_000 => self::ISS_ALTITUDE,
            $m >= 200_000 => self::HALFWAY_TO_ISS,
            $m >= 100_000 => self::KARMAN_LINE,
            $m >= 88_448 => self::EVEREST_TEN_TIMES,
            $m >= 53_069 => self::EVEREST_SIX_TIMES,
            $m >= 39_000 => self::BAUMGARTNER_SPACE_JUMP,
            $m >= 26_658 => self::EVEREST_THREE_TIMES,
            $m >= 17_772 => self::EVEREST_TWICE,
            $m >= 12_000 => self::CRUISING_ALTITUDE,
            $m >= 8_849 => self::MOUNT_EVEREST,
            $m >= 6_190 => self::DENALI,
            $m >= 4_808 => self::MONT_BLANC,
            $m >= 3_776 => self::MOUNT_FUJI,
            $m >= 2_469 => self::ALPE_D_HUEZ,
            $m >= 1_000 => self::TWO_EIFFEL_TOWERS,
            $m >= 500 => self::EMPIRE_STATE_BUILDING,
            $m >= 300 => self::EIFFEL_TOWER,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::EIFFEL_TOWER => $translator->trans("That's the height of the Eiffel Tower", locale: $locale),
            self::EMPIRE_STATE_BUILDING => $translator->trans("That's taller than the Empire State Building", locale: $locale),
            self::TWO_EIFFEL_TOWERS => $translator->trans("That's more than 2 Eiffel Towers stacked up", locale: $locale),
            self::ALPE_D_HUEZ => $translator->trans("That's the height of the tallest Alpe d'Huez finish", locale: $locale),
            self::MOUNT_FUJI => $translator->trans("That's the height of Mount Fuji", locale: $locale),
            self::MONT_BLANC => $translator->trans("That's the height of Mont Blanc, the rooftop of the Alps", locale: $locale),
            self::DENALI => $translator->trans("That's the height of Denali, the tallest peak in North America", locale: $locale),
            self::MOUNT_EVEREST => $translator->trans("That's the height of Mount Everest", locale: $locale),
            self::CRUISING_ALTITUDE => $translator->trans("That's cruising altitude of a commercial airplane", locale: $locale),
            self::EVEREST_TWICE => $translator->trans("That's climbing Mount Everest twice", locale: $locale),
            self::EVEREST_THREE_TIMES => $translator->trans("That's climbing Mount Everest 3 times", locale: $locale),
            self::BAUMGARTNER_SPACE_JUMP => $translator->trans("That's higher than Felix Baumgartner's space jump", locale: $locale),
            self::EVEREST_SIX_TIMES => $translator->trans("That's climbing Mount Everest 6 times", locale: $locale),
            self::EVEREST_TEN_TIMES => $translator->trans("That's climbing Mount Everest 10 times", locale: $locale),
            self::KARMAN_LINE => $translator->trans("That's the edge of space, the Kármán line", locale: $locale),
            self::HALFWAY_TO_ISS => $translator->trans("That's more than halfway to the ISS", locale: $locale),
            self::ISS_ALTITUDE => $translator->trans("That's the altitude of the International Space Station", locale: $locale),
            self::HIGHER_THAN_ISS => $translator->trans("That's higher than the International Space Station's orbit", locale: $locale),
            self::EDGE_OF_SPACE_FIVE_TIMES => $translator->trans("That's climbing to the edge of space and back 5 times", locale: $locale),
        };
    }
}
