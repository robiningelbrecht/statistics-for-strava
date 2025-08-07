<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigFunction;

final class HtmlTwigExtension
{
    /** @var array<string, int> */
    private static array $seenIds;

    #[AsTwigFunction('uniqueNumberForId')]
    public function uniqueNumberForId(string $id): string
    {
        if (!isset(HtmlTwigExtension::$seenIds)) {
            HtmlTwigExtension::$seenIds = [];
        }

        if (isset(HtmlTwigExtension::$seenIds[$id])) {
            ++HtmlTwigExtension::$seenIds[$id];
        } else {
            HtmlTwigExtension::$seenIds[$id] = 1;
        }

        return '--'.HtmlTwigExtension::$seenIds[$id];
    }
}
