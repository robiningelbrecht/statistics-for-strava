<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

final readonly class RewindItem
{
    private function __construct(
        private string $icon,
        private string $title,
        private ?string $subTitle,
        private string $content,
        private ?int $totalMetric,
        private ?string $totalMetricLabel,
        private bool $isPlaceHolderForComparison,
    ) {
    }

    public static function from(
        string $icon,
        string $title,
        ?string $subTitle,
        string $content,
        ?int $totalMetric = null,
        ?string $totalMetricLabel = null,
        bool $isPlaceHolderForComparison = false,
    ): self {
        return new self(
            icon: $icon,
            title: $title,
            subTitle: $subTitle,
            content: $content,
            totalMetric: $totalMetric,
            totalMetricLabel: $totalMetricLabel,
            isPlaceHolderForComparison: $isPlaceHolderForComparison,
        );
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function hasTotalMetric(): bool
    {
        return null !== $this->totalMetric && null !== $this->totalMetricLabel;
    }

    public function getTotalMetric(): ?int
    {
        return $this->totalMetric;
    }

    public function getTotalMetricLabel(): ?string
    {
        return $this->totalMetricLabel;
    }

    public function isPlaceHolderForComparison(): bool
    {
        return $this->isPlaceHolderForComparison;
    }
}
