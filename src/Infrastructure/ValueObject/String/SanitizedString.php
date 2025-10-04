<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class SanitizedString extends NonEmptyStringLiteral
{
    public static function fromString(string $string): static
    {
        /** @var string $sanitized */
        $sanitized = preg_replace('/-+/', '-', str_replace(' ', '-',
            preg_replace('/[^a-z0-9] /', '', strtolower($string)) // @phpstan-ignore argument.type
        ));

        return parent::fromString($sanitized);
    }

    public static function fromOptionalString(?string $string = null): ?static
    {
        if (!$string) {
            return null;
        }

        return self::fromString($string);
    }
}
