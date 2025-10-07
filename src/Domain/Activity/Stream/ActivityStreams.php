<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivityStream>
 */
class ActivityStreams extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityStream::class;
    }

    public function filterOnType(StreamType $streamType): ?ActivityStream
    {
        return $this->filter(fn (ActivityStream $stream): bool => $stream->getStreamType() === $streamType)->getFirst();
    }
}
