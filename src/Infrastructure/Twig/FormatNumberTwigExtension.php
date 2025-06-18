<?php

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigFilter;

final readonly class FormatNumberTwigExtension
{
    #[AsTwigFilter('formatNumber')]
    public static function doFormat(?float $number, int $precision): string
    {
        if (is_null($number)) {
            return '0';
        }

        return number_format(round($number, $precision), $precision, '.', ' ');
    }
}
