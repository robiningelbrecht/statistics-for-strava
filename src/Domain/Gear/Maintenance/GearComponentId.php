<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class GearComponentId extends Identifier
{
    public static function getPrefix(): string
    {
        return '';
    }
}
