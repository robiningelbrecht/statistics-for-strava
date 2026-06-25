<?php

declare(strict_types=1);

namespace App\Domain\Image;

final readonly class RemovedImage
{
    public function __construct(
        private string $path,
    ) {
    }

    public function getPath(): ImagePath
    {
        return ImagePath::fromLocalImagePath($this->path);
    }
}
