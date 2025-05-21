<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\ValueObject\String\BasePath;

final readonly class UrlTwigExtension
{
    public function __construct(
        private ?BasePath $basePath,
    ) {
    }

    public function toRelativeUrl(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        if (null === $this->basePath) {
            return $path;
        }

        return '/'.trim((string) $this->basePath, '/').$path;
    }
}
