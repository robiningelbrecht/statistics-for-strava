<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

final readonly class UrlTwigExtension
{
    public function toAbsoluteUrl(string $url): string
    {
        return '/'.ltrim($url, '/');
    }
}
