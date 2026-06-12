<?php

declare(strict_types=1);

namespace App\Application;

final readonly class AppName implements \Stringable
{
    public const string LABEL = 'Statistics for Strava';

    public function __toString(): string
    {
        return self::LABEL;
    }
}
