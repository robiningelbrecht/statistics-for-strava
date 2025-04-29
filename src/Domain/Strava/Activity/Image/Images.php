<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Image;

use App\Infrastructure\ValueObject\Collection;

final class Images extends Collection
{
    public function getItemClassName(): string
    {
        return Image::class;
    }
}
