<?php

declare(strict_types=1);

namespace App\Domain\Milestone\FunComparison;

use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DistanceFunComparison: string implements FunComparison
{
    case CENTURY_RIDE = 'distanceCenturyRide';
    case NETHERLANDS_LENGTH = 'distanceNetherlandsLength';
    case AMSTERDAM_BERLIN_RETURN = 'distanceAmsterdamBerlinReturn';
    case FRANCE_LENGTH = 'distanceFranceLength';
    case GREAT_BRITAIN_LENGTH = 'distanceGreatBritainLength';
    case RHINE_RIVER = 'distanceRhineRiver';
    case WIDER_THAN_AUSTRALIA = 'distanceWiderThanAustralia';
    case GREAT_WALL_OF_CHINA = 'distanceGreatWallOfChina';
    case USA_COAST_TO_COAST = 'distanceUsaCoastToCoast';
    case TRANS_SIBERIAN_RAILWAY = 'distanceTransSiberianRailway';
    case EARTH_DIAMETER = 'distanceEarthDiameter';
    case HALFWAY_AROUND_EARTH = 'distanceHalfwayAroundEarth';
    case EARTH_CIRCUMFERENCE = 'distanceEarthCircumference';
    case TWICE_AROUND_EARTH = 'distanceTwiceAroundEarth';
    case TWO_AND_HALF_TIMES_AROUND_EARTH = 'distanceTwoAndHalfTimesAroundEarth';
    case EARTH_TO_MOON = 'distanceEarthToMoon';

    public static function resolve(Kilometer $distance): ?self
    {
        $km = $distance->toFloat();

        return match (true) {
            $km >= 384_400 => self::EARTH_TO_MOON,
            $km >= 100_000 => self::TWO_AND_HALF_TIMES_AROUND_EARTH,
            $km >= 80_000 => self::TWICE_AROUND_EARTH,
            $km >= 40_075 => self::EARTH_CIRCUMFERENCE,
            $km >= 20_038 => self::HALFWAY_AROUND_EARTH,
            $km >= 12_742 => self::EARTH_DIAMETER,
            $km >= 9_288 => self::TRANS_SIBERIAN_RAILWAY,
            $km >= 6_671 => self::USA_COAST_TO_COAST,
            $km >= 5_000 => self::GREAT_WALL_OF_CHINA,
            $km >= 3_500 => self::WIDER_THAN_AUSTRALIA,
            $km >= 2_300 => self::RHINE_RIVER,
            $km >= 1_400 => self::GREAT_BRITAIN_LENGTH,
            $km >= 1_000 => self::FRANCE_LENGTH,
            $km >= 600 => self::AMSTERDAM_BERLIN_RETURN,
            $km >= 300 => self::NETHERLANDS_LENGTH,
            $km >= 100 => self::CENTURY_RIDE,
            default => null,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::CENTURY_RIDE => $translator->trans("That's a century ride", locale: $locale),
            self::NETHERLANDS_LENGTH => $translator->trans("That's the length of the Netherlands from north to south", locale: $locale),
            self::AMSTERDAM_BERLIN_RETURN => $translator->trans("That's the distance from Amsterdam to Berlin and back", locale: $locale),
            self::FRANCE_LENGTH => $translator->trans("That's roughly the length of France", locale: $locale),
            self::GREAT_BRITAIN_LENGTH => $translator->trans("That's the length of Great Britain from north to south", locale: $locale),
            self::RHINE_RIVER => $translator->trans("That's the length of the Rhine river", locale: $locale),
            self::WIDER_THAN_AUSTRALIA => $translator->trans("That's wider than Australia", locale: $locale),
            self::GREAT_WALL_OF_CHINA => $translator->trans("That's the length of the Great Wall of China", locale: $locale),
            self::USA_COAST_TO_COAST => $translator->trans("That's the width of the United States coast to coast", locale: $locale),
            self::TRANS_SIBERIAN_RAILWAY => $translator->trans("That's the length of the Trans-Siberian Railway", locale: $locale),
            self::EARTH_DIAMETER => $translator->trans("That's the diameter of the Earth", locale: $locale),
            self::HALFWAY_AROUND_EARTH => $translator->trans("That's more than halfway around the Earth", locale: $locale),
            self::EARTH_CIRCUMFERENCE => $translator->trans("That's the circumference of the Earth", locale: $locale),
            self::TWICE_AROUND_EARTH => $translator->trans("That's twice around the Earth", locale: $locale),
            self::TWO_AND_HALF_TIMES_AROUND_EARTH => $translator->trans("That's more than 2.5 times around the Earth", locale: $locale),
            self::EARTH_TO_MOON => $translator->trans("That's the distance from Earth to the Moon", locale: $locale),
        };
    }
}
