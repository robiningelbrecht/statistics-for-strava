<?php

declare(strict_types=1);

namespace App\Infrastructure\Localisation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface TranslatableWithDescription extends TranslatableInterface
{
    public function transDescription(TranslatorInterface $translator, ?string $locale = null): string;
}
