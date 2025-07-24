<?php

declare(strict_types=1);

namespace App\Domain\App;

use App\Infrastructure\ValueObject\String\CssColor;
use App\Infrastructure\ValueObject\String\Url;

final readonly class HeatmapConfig implements \JsonSerializable
{
    private function __construct(
        private CssColor $polylineColor,
        private Url $tileLayerUrl,
        private bool $enableGreyScale,
    ) {
    }

    public static function create(
        string $polylineColor,
        string $tileLayerUrl,
        bool $enableGreyScale,
    ): self {
        return new self(
            polylineColor: CssColor::fromString($polylineColor),
            tileLayerUrl: Url::fromString($tileLayerUrl),
            enableGreyScale: $enableGreyScale,
        );
    }

    public function getPolylineColor(): CssColor
    {
        return $this->polylineColor;
    }

    public function getTileLayerUrl(): Url
    {
        return $this->tileLayerUrl;
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
            'tileLayerUrl' => $this->getTileLayerUrl(),
            'enableGreyScale' => $this->enableGreyScale(),
        ];
    }
}
