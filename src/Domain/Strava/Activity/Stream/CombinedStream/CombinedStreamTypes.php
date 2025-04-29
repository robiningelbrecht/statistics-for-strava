<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Infrastructure\ValueObject\Collection;

final class CombinedStreamTypes extends Collection
{
    public function getItemClassName(): string
    {
        return CombinedStreamType::class;
    }
}
