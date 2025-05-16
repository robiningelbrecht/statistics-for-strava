<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class AthleteBirthDate extends SerializableDateTime
{
    #[\Override]
    public static function fromString(string $string): self
    {
        try {
            $birthDate = new self($string);
        } catch (\DateMalformedStringException) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s" set in ATHLETE_BIRTHDAY in .env file', $string));
        }

        return $birthDate;
    }
}
