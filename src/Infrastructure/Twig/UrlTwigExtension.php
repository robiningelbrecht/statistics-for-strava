<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\App\AppUrl;
use Twig\Attribute\AsTwigFunction;

final readonly class UrlTwigExtension
{
    public function __construct(
        private AppUrl $appUrl,
    ) {
    }

    #[AsTwigFunction('relativeUrl')]
    public function toRelativeUrl(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        if (null === $this->appUrl->getBasePath()) {
            return $path;
        }

        return '/'.trim($this->appUrl->getBasePath(), '/').$path;
    }

    #[AsTwigFunction('placeholderImage')]
    public function placeholderImage(): string
    {
        return $this->toRelativeUrl('/assets/placeholder.webp');
    }
}
