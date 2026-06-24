<?php

declare(strict_types=1);

namespace App\Domain\Activity\UpdateActivity;

use App\Domain\Activity\Image\ActivityImagePath;

final readonly class ActivityRemovedImage
{
    public function __construct(
        private string $path,
    ) {
    }

    public function getPath(): ActivityImagePath
    {
        return ActivityImagePath::fromLocalImagePath($this->path);
    }
}
