<?php

declare(strict_types=1);

namespace App\Domain\Challenge;

use App\Infrastructure\ValueObject\Identifier\Identifier;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ChallengeId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'challenge-';
    }

    public static function fromDateAndName(
        SerializableDateTime $createdOn,
        string $name,
    ): self {
        /** @var string $sanitizedString */
        $sanitizedString = preg_replace("/\s+/", '_', substr($name, 0, 250));

        return self::fromUnprefixed(
            $createdOn->format('Y-m_').strtolower($sanitizedString)
        );
    }
}
