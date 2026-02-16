<?php

declare(strict_types=1);

namespace App\Application;

final readonly class AppVersion
{
    private const int MAJOR = 4;
    private const int MINOR = 6;
    private const int PATCH = 4;

    public static function getSemanticVersion(): string
    {
        return sprintf('v%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);
    }
}
