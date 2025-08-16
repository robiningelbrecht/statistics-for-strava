<?php

declare(strict_types=1);

namespace App\Domain\Athlete;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class AthleteBirthDate extends SerializableDateTime
{
    #[\Override]
    public static function fromString(string $string): self
    {
        try {
            $birthDate = new self($string);
        } catch (\DateMalformedStringException) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s" set for athlete birthday in config.yaml file', $string));
        }

        return $birthDate;
    }
}
