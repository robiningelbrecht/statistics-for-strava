<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Infrastructure\ValueObject\String\NonEmptyStringLiteral;

final readonly class Tag extends NonEmptyStringLiteral
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (str_contains($value, ' ')) {
            throw new InvalidGearMaintenanceConfig(sprintf('Invalid component tag "%s", no spaces allowed.', $value));
        }
    }

    public static function fromTags(string ...$tags): self
    {
        return self::fromString(implode('-', $tags));
    }
}
