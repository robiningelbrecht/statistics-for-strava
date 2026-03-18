<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

use App\Application\AppUrl;

final readonly class Path
{
    private function __construct(
        private string $path,
        private AppUrl $appUrl,
    ) {
    }

    public static function from(string $path, AppUrl $appUrl): self
    {
        return new self(
            path: $path,
            appUrl: $appUrl
        );
    }

    public function toRelativePath(): string
    {
        $path = '/'.ltrim($this->path, '/');
        if (null === $this->appUrl->getBasePath()) {
            return $path;
        }

        return '/'.trim($this->appUrl->getBasePath(), '/').$path;
    }
}
