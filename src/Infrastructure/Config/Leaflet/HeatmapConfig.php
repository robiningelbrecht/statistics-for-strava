<?php

declare(strict_types=1);

namespace App\Infrastructure\Config\Leaflet;

use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Geography\ZoomLevel;

final readonly class HeatmapConfig implements \JsonSerializable
{
    private function __construct(
        private LeafletConfig $leafletConfig,
        private ?Coordinate $initialCenter,
        private ?ZoomLevel $initialZoom,
    ) {
    }

    /**
     * @param array{float, float}|null $initialCenter [lat, lng]
     */
    public static function create(
        LeafletConfig $leafletConfig,
        ?array $initialCenter = null,
        ?int $initialZoom = null,
    ): self {
        return new self(
            leafletConfig: $leafletConfig,
            initialCenter: $initialCenter ? Coordinate::createFromLatAndLng(
                Latitude::fromString((string) $initialCenter[0]),
                Longitude::fromString((string) $initialCenter[1]),
            ) : null,
            initialZoom: ZoomLevel::fromOptionalInt($initialZoom),
        );
    }

    public function getLeafletConfig(): LeafletConfig
    {
        return $this->leafletConfig;
    }

    public function getInitialCenter(): ?Coordinate
    {
        return $this->initialCenter;
    }

    public function getInitialZoom(): ?ZoomLevel
    {
        return $this->initialZoom;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'polylineColor' => $this->getLeafletConfig()->getPolylineColor(),
            'tileLayerUrls' => $this->getLeafletConfig()->getTileLayerUrls(),
            'enableGreyScale' => $this->getLeafletConfig()->enableGreyScale(),
            'initialCenter' => $this->getInitialCenter(),
            'initialZoom' => $this->getInitialZoom()?->getValue(),
        ];
    }
}
