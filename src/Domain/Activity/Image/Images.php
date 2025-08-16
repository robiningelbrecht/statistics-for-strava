<?php

declare(strict_types=1);

namespace App\Domain\Activity\Image;

use App\Domain\Activity\SportType\SportType;

final class Images
{
    /** @var array <string, array<int, Image>> */
    private array $imagesPerSportType;
    /** @var Image[] */
    private array $allImages;

    private function __construct()
    {
        $this->imagesPerSportType = [];
        $this->allImages = [];
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(SportType $sportType, Image $image): void
    {
        $this->imagesPerSportType[$sportType->value][] = $image;
        $this->allImages[] = $image;
    }

    /**
     * @return Image[]
     */
    public function getForSportType(SportType $sportType): array
    {
        return $this->imagesPerSportType[$sportType->value] ?? [];
    }

    /**
     * @return Image[]
     */
    public function getAll(): array
    {
        return $this->allImages;
    }
}
