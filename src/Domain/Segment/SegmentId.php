<?php

declare(strict_types=1);

namespace App\Domain\Segment;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class SegmentId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'segment-';
    }
}
