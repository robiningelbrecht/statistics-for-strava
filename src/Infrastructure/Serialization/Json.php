<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

class Json
{
    public static function encode(mixed $value, int $depth = 512): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR, max(1, $depth));
    }

    public static function encodePretty(mixed $value, int $depth = 512): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, max(1, $depth));
    }

    public static function decode(string $json, bool $assoc = true, int $depth = 512): mixed
    {
        return json_decode($json ?: '', $assoc, max(1, $depth), JSON_THROW_ON_ERROR);
    }

    public static function encodeAndDecode(mixed $value, int $depth = 512): mixed
    {
        return self::decode(self::encode($value, $depth));
    }
}
