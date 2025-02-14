<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Route;

use App\Infrastructure\ValueObject\Collection;

final class Routes extends Collection
{
    public function getItemClassName(): string
    {
        return Route::class;
    }
}
