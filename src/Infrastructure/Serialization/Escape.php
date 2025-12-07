<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

final readonly class Escape
{
    public static function forJsonEncode(string $string): string
    {
        return $string
                |> (fn (string $value): string => str_replace(['"', '\''], '', $value))
                |> (fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
}
