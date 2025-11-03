<?php

declare(strict_types=1);

namespace App\BuildApp\BuildHeatmapHtml;

use App\Infrastructure\ValueObject\String\CssColor;
use App\Infrastructure\ValueObject\String\Url;

final readonly class HeatmapConfig implements \JsonSerializable
{
    private function __construct(
        private CssColor $polylineColor,
        /** @var Url[] */
        private array $tileLayerUrls,
        private bool $enableGreyScale,
    ) {
    }

    /**
     * @param string[]|string $tileLayerUrls
     */
    public static function create(
        string $polylineColor,
        string|array $tileLayerUrls,
        bool $enableGreyScale,
    ): self {
        if (is_string($tileLayerUrls)) {
            $tileLayerUrls = [$tileLayerUrls];
        }

        return new self(
            polylineColor: CssColor::fromString($polylineColor),
            tileLayerUrls: array_map(Url::fromString(...), $tileLayerUrls),
            enableGreyScale: $enableGreyScale,
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

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'polylineColor' => $this->getPolylineColor(),
            'tileLayerUrls' => $this->getTileLayerUrls(),
            'enableGreyScale' => $this->enableGreyScale(),
        ];
    }
}
