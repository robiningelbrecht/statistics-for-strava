<?php

declare(strict_types=1);

namespace App\Application;

final readonly class AppVersion
{
    private const int MAJOR = 4;
    private const int MINOR = 8;
    private const int PATCH = 8;

    public static function getSemanticVersion(): string
    {
        return sprintf('v%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH);
    }

    public static function isAtLeastVersion5(): bool
    {
        return version_compare(
            sprintf('%d.%d.%d', self::MAJOR, self::MINOR, self::PATCH),
            '5.0.0',
            '>='
        );
    }
}
