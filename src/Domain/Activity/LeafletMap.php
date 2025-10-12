<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Infrastructure\ValueObject\Geography\Coordinate;

interface LeafletMap
{
    public function getTileLayer(): ?string;

    public function getOverlayImageUrl(): ?string;

    /**
     * @return Coordinate[]
     */
    public function getBounds(): array;

    public function getMaxZoom(): int;

    public function getMinZoom(): int;

    public function getBackgroundColor(): string;
}
