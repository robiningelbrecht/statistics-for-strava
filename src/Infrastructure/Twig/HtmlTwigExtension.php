<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigFunction;

final class HtmlTwigExtension
{
    /** @var array<string, int> */
    public static array $seenIds = [];

    #[AsTwigFunction('uniqueNumberForId')]
    public function uniqueNumberForId(string $id): string
    {
        HtmlTwigExtension::$seenIds[$id] = (HtmlTwigExtension::$seenIds[$id] ?? 0) + 1;

        return '--'.HtmlTwigExtension::$seenIds[$id];
    }
}
