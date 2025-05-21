<?php

declare(strict_types=1);

namespace App\Domain\App;

use App\Infrastructure\ValueObject\String\Url;

final readonly class AppUrl extends Url
{
    public function getBasePath(): ?string
    {
        if (!$basePath = parse_url((string) $this, PHP_URL_PATH)) {
            return null;
        }

        return ltrim($basePath, '/') ?: null;
    }
}
