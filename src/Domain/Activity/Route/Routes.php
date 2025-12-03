<?php

declare(strict_types=1);

namespace App\Domain\Activity\Route;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<\App\Domain\Activity\Route\Route>
 */
final class Routes extends Collection
{
    public function getItemClassName(): string
    {
        return Route::class;
    }
}
