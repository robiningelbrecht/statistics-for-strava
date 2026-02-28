<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Milestone>
 */
final class Milestones extends Collection
{
    public function getItemClassName(): string
    {
        return Milestone::class;
    }
}
