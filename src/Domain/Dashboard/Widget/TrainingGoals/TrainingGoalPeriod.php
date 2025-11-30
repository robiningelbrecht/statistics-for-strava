<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingGoals;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TrainingGoalPeriod: string implements TranslatableInterface
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case LIFETIME = 'lifetime';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::WEEKLY => $translator->trans('Weekly', locale: $locale),
            self::MONTHLY => $translator->trans('Monthly', locale: $locale),
            self::YEARLY => $translator->trans('Yearly', locale: $locale),
            self::LIFETIME => $translator->trans('Lifetime', locale: $locale),
        };
    }
}
