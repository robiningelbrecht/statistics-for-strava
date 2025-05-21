<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\App\AppUrl;

final readonly class UrlTwigExtension
{
    public function __construct(
        private AppUrl $appUrl,
    ) {
    }

    public function toRelativeUrl(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        if (null === $this->appUrl->getBasePath()) {
            return $path;
        }

        return '/'.trim($this->appUrl->getBasePath(), '/').$path;
    }
}
