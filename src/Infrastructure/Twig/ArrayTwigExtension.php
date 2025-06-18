<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigFilter;

final readonly class ArrayTwigExtension
{
    /**
     * @param array<mixed,mixed> $values
     *
     * @return array<mixed,mixed>
     */
    #[AsTwigFilter('array_values')]
    public static function doArrayValues(array $values): array
    {
        return array_values($values);
    }
}
