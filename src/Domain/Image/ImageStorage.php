<?php

declare(strict_types=1);

namespace App\Domain\Image;

use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;

final readonly class ImageStorage
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function store(NewImage $newImage, ImageDirectory $directory): ImagePath
    {
        $fileSystemPath = sprintf(
            '%s/%s.%s',
            $directory->value,
            $this->uuidFactory->random(),
            $newImage->getFilename()->getExtension(),
        );
        $this->fileStorage->write($fileSystemPath, $newImage->getContent());

        return ImagePath::fromFileSystemPath($fileSystemPath);
    }

    public function remove(ImagePath $path): void
    {
        $fileSystemPath = $path->toFileSystemPath();
        if ($this->fileStorage->fileExists($fileSystemPath)) {
            $this->fileStorage->delete($fileSystemPath);
        }
    }
}
