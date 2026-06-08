<?php

declare(strict_types=1);

namespace App\Application;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AppName implements TranslatableInterface
{
    public const string LABEL = 'Statistics for Strava';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('Statistics for Strava', locale: $locale);
    }
}
