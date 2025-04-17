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
    ) {
    }

    public static function from(
        string $icon,
        string $title,
        ?string $subTitle,
        string $content,
    ): self {
        return new self(
            icon: $icon,
            title: $title,
            subTitle: $subTitle,
            content: $content
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
}
