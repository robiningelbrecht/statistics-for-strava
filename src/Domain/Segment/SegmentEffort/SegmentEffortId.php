<?php

declare(strict_types=1);

namespace App\Domain\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class SegmentEffortId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'segmentEffort-';
    }
}
