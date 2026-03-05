<?php

declare(strict_types=1);

namespace App\Infrastructure\Localisation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface TranslatableWithDescription extends TranslatableInterface
{
    /**
     * @param array<string, string> $parameters
     */
    public function transDescription(TranslatorInterface $translator, array $parameters = [], ?string $domain = null, ?string $locale = null): string;
}
