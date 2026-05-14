<?php

declare(strict_types=1);

namespace App\Application\Build\BuildHeatmapHtml;

use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Geography\ZoomLevel;
use App\Infrastructure\ValueObject\String\CssColor;
use App\Infrastructure\ValueObject\String\Url;

final readonly class HeatmapConfig implements \JsonSerializable
{
    private function __construct(
        private CssColor $polylineColor,
        /** @var Url[] */
        private array $tileLayerUrls,
        private bool $enableGreyScale,
        private ?Coordinate $initialCenter,
        private ?ZoomLevel $initialZoom,
    ) {
    }

    /**
     * @param string[]|string          $tileLayerUrls
     * @param array{float, float}|null $initialCenter [lat, lng]
     */
    public static function create(
        string $polylineColor,
        string|array $tileLayerUrls,
        bool $enableGreyScale,
        ?array $initialCenter = null,
        ?int $initialZoom = null,
    ): self {
        if (is_string($tileLayerUrls)) {
            $tileLayerUrls = [$tileLayerUrls];
        }

        return new self(
            polylineColor: CssColor::fromString($polylineColor),
            tileLayerUrls: array_map(Url::fromString(...), $tileLayerUrls),
            enableGreyScale: $enableGreyScale,
            initialCenter: $initialCenter ? Coordinate::createFromLatAndLng(
                Latitude::fromString((string) $initialCenter[0]),
                Longitude::fromString((string) $initialCenter[1]),
            ) : null,
            initialZoom: ZoomLevel::fromOptionalInt($initialZoom),
        );
    }

    public function getPolylineColor(): CssColor
    {
        return $this->polylineColor;
    }

    /**
     * @return Url[]
     */
    public function getTileLayerUrls(): array
    {
        return $this->tileLayerUrls;
    }

    public function enableGreyScale(): bool
    {
        return $this->enableGreyScale;
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
            'polylineColor' => $this->getPolylineColor(),
            'tileLayerUrls' => $this->getTileLayerUrls(),
            'enableGreyScale' => $this->enableGreyScale(),
            'initialCenter' => $this->getInitialCenter(),
            'initialZoom' => $this->getInitialZoom()?->getValue(),
        ];
    }
}
