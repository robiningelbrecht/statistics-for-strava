<?php

declare(strict_types=1);

namespace App\Domain\Segment;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Segment>
 */
final class Segments extends Collection
{
    public function getItemClassName(): string
    {
        return Segment::class;
    }
}
