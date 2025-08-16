<?php

declare(strict_types=1);

namespace App\Domain\Challenge;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Challenge>
 */
class Challenges extends Collection
{
    public function getItemClassName(): string
    {
        return Challenge::class;
    }
}
