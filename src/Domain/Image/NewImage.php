<?php

declare(strict_types=1);

namespace App\Domain\Image;

use App\Infrastructure\ValueObject\String\Path;

final readonly class NewImage
{
    public function __construct(
        private Path $filename,
        private string $content,
    ) {
    }

    public function getFilename(): Path
    {
        return $this->filename;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
