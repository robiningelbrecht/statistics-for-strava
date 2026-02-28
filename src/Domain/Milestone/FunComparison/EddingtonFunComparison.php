<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use Symfony\Contracts\Translation\TranslatorInterface;

enum EddingtonFunComparison: string implements FunComparison
{
    case BUILDING_CONSISTENCY = 'eddingtonBuildingConsistency';
    case IMPRESSIVE_DEDICATION = 'eddingtonImpressiveDedication';
    case EXPONENTIALLY_TOUGHER = 'eddingtonExponentiallyTougher';
    case TRUE_CENTURION = 'eddingtonTrueCenturion';
    case ELITE_TERRITORY = 'eddingtonEliteTerritory';
    case LEGENDARY_ENDURANCE = 'eddingtonLegendaryEndurance';

    public static function resolve(int $number): ?self
    {
        return match (true) {
            $number >= 200 => self::LEGENDARY_ENDURANCE,
            $number >= 150 => self::ELITE_TERRITORY,
            $number >= 100 => self::TRUE_CENTURION,
            $number >= 75 => self::EXPONENTIALLY_TOUGHER,
            $number >= 50 => self::IMPRESSIVE_DEDICATION,
            $number >= 25 => self::BUILDING_CONSISTENCY,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::BUILDING_CONSISTENCY => $translator->trans("That's 25 days of 25+ km, building serious consistency", locale: $locale),
            self::IMPRESSIVE_DEDICATION => $translator->trans("That's 50 days of at least 50 km, impressive dedication", locale: $locale),
            self::EXPONENTIALLY_TOUGHER => $translator->trans("That's harder than it sounds, each number gets exponentially tougher", locale: $locale),
            self::TRUE_CENTURION => $translator->trans("That's at least 100 days of 100+ km, a true centurion", locale: $locale),
            self::ELITE_TERRITORY => $translator->trans("That's elite athlete territory", locale: $locale),
            self::LEGENDARY_ENDURANCE => $translator->trans("That's legendary endurance", locale: $locale),
        };
    }
}
