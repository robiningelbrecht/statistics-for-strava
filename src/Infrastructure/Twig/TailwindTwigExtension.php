<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use TailwindMerge\TailwindMerge;
use Twig\Attribute\AsTwigFilter;

final readonly class TailwindTwigExtension
{
    #[AsTwigFilter('tailwind_merge')]
    public function merge(string|array|null $classes, array $configuration = []): string
    {
        return TailwindMerge::factory()
            ->withConfiguration($configuration)->make()
            ->merge($classes);
    }
}
