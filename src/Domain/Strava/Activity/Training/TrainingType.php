<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Training;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TrainingType implements TranslatableInterface
{
    case POLARIZED;
    case PYRAMIDAL;
    case THRESHOLD;
    case HIIT;
    case BASE;
    case UNIQUE_OTHER;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::POLARIZED => $translator->trans('Polarized', locale: $locale),
            self::PYRAMIDAL => $translator->trans('Pyramidal', locale: $locale),
            self::THRESHOLD => $translator->trans('Threshold', locale: $locale),
            self::HIIT => $translator->trans('HIIT', locale: $locale),
            self::BASE => $translator->trans('Base', locale: $locale),
            self::UNIQUE_OTHER => $translator->trans('Unique/other', locale: $locale),
        };
    }
}
