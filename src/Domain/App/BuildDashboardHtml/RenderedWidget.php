<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

final readonly class RenderedWidget
{
    public function __construct(
        private string $renderedHtml,
        private int $width,
    ) {
    }

    public function getRenderedHtml(): string
    {
        return $this->renderedHtml;
    }

    public function getWidth(): int
    {
        return $this->width;
    }
}
