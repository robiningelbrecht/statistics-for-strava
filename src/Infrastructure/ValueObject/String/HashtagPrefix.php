<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class HashtagPrefix extends NonEmptyStringLiteral
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (str_starts_with($value, '#')) {
            throw new \InvalidArgumentException(sprintf('HashtagPrefix %s can not start with #', $value));
        }
        if (str_ends_with($value, '-')) {
            throw new \InvalidArgumentException(sprintf('HashtagPrefix %s can not to end with -', $value));
        }
    }

    public function __toString(): string
    {
        return sprintf('#%s', parent::__toString());
    }
}
