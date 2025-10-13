<?php

declare(strict_types=1);

namespace App\Domain\Activity;

final readonly class RealWorldMap implements LeafletMap
{
    public function getLabel(): string
    {
        return 'Real world';
    }

    public function getTileLayer(): string
    {
        return 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    }

    public function getOverlayImageUrl(): ?string
    {
        return null;
    }

    public function getBounds(): array
    {
        return [];
    }

    public function getMaxZoom(): int
    {
        return 17;
    }

    public function getMinZoom(): int
    {
        return 1;
    }

    public function getBackgroundColor(): string
    {
        return '#bbbbb7';
    }
}
