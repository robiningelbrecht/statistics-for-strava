<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig\Filters;

use App\Infrastructure\Serialization\Json;
use Twig\Attribute\AsTwigFunction;

final class FilterStorageTwigExtension
{
    /**
     * @param array<string, mixed> $filters
     */
    #[AsTwigFunction('dataFilters', isSafe: ['html'])]
    public function dataFilters(string $filterName, array $filters): string
    {
        return Json::encode([FilterName::from($filterName)->value => $filters]);
    }
}
