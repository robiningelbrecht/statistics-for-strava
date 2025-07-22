<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use TailwindMerge\TailwindMerge;
use Twig\Attribute\AsTwigFilter;

/**
 * @codeCoverageIgnore
 */
final readonly class TailwindTwigExtension
{
    /**
     * @param array<array<string>|string>|string $classes
     * @param array<string, mixed>               $configuration
     */
    #[AsTwigFilter('tailwind_merge')]
    public function merge(string|array $classes, array $configuration = []): string
    {
        return TailwindMerge::factory()
            ->withConfiguration($configuration)->make()
            ->merge($classes);
    }
}
