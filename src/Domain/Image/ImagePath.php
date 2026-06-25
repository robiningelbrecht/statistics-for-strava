<?php

declare(strict_types=1);

namespace App\Domain\Image;

final readonly class ImagePath
{
    private function __construct(
        private string $localImagePath,
    ) {
    }

    public static function fromLocalImagePath(string $localImagePath): self
    {
        return new self(ltrim($localImagePath, '/'));
    }

    public static function fromFileSystemPath(string $fileSystemPath): self
    {
        return new self('files/'.ltrim($fileSystemPath, '/'));
    }

    public function toLocalImagePath(): string
    {
        return $this->localImagePath;
    }

    public function toFileSystemPath(): string
    {
        return (string) preg_replace('#^files/#', '', $this->localImagePath);
    }
}
