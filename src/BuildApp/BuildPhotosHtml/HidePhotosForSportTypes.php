<?php

declare(strict_types=1);

namespace App\BuildApp\BuildPhotosHtml;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Collection;

final class HidePhotosForSportTypes extends Collection
{
    public function getItemClassName(): string
    {
        return SportType::class;
    }

    /**
     * @param string[] $sportTypes
     */
    public static function from(array $sportTypes): self
    {
        if (0 === count($sportTypes)) {
            return self::empty();
        }

        return self::fromArray(
            array_map(
                SportType::from(...),
                $sportTypes
            )
        );
    }
}
