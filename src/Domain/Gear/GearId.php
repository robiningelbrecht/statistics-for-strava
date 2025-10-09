<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class GearId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'gear-';
    }

    public static function none(): self
    {
        return self::fromUnprefixed('none');
    }

    public function isPrefixedWithStravaPrefix(): bool
    {
        $unprefixed = $this->toUnprefixedString();

        return str_starts_with($unprefixed, 'b') || str_starts_with($unprefixed, 'g');
    }

    public function matches(GearId $other): bool
    {
        if ($this->toUnprefixedString() === $other->toUnprefixedString()) {
            return true;
        }

        $unprefixed = $this->toUnprefixedString();
        if ($this->isPrefixedWithStravaPrefix()) {
            $unprefixed = substr($unprefixed, 1);
        }

        $otherUnprefixed = $other->toUnprefixedString();
        if ($other->isPrefixedWithStravaPrefix()) {
            $otherUnprefixed = substr($otherUnprefixed, 1);
        }

        return $unprefixed === $otherUnprefixed;
    }
}
