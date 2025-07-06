<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

final readonly class Escape
{
    public static function htmlSpecialChars(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
