<?php

declare(strict_types=1);

namespace App\BuildApp;

final readonly class AppVersion
{
    private const int MAJOR = 3;
    private const int MINOR = 8;
    private const int PATCH = 4;

    public static function getSemanticVersion(): string
    {
        return sprintf('v%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);
    }
}
