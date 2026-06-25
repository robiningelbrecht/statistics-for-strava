<?php

declare(strict_types=1);

namespace App\Domain\Gear\UpdateGear;

use App\Domain\Gear\GearRepository;
use App\Domain\Image\ImagePath;
use App\Domain\Image\NewImage;
use App\Domain\Image\RemovedImage;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;
use Money\Money;

final readonly class UpdateGearCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGear);

        $gear = $this->gearRepository->find($command->getGearId())
            ->withName($command->getName())
            ->withIsRetired($command->isRetired());

        if ($command->getPurchasePrice() instanceof Money) {
            $gear = $gear->withPurchasePrice($command->getPurchasePrice());
        }

        $newImage = $command->getNewImage();
        $removedImage = $command->getRemovedImage();

        if ($newImage instanceof NewImage) {
            $fileSystemPath = sprintf('gear/%s.%s', $this->uuidFactory->random(), $newImage->getFilename()->getExtension());
            $this->fileStorage->write($fileSystemPath, $newImage->getContent());
            $gear = $gear->withLocalImagePath(ImagePath::fromFileSystemPath($fileSystemPath)->toLocalImagePath());
        } elseif ($removedImage instanceof RemovedImage) {
            $gear = $gear->withLocalImagePath(null);
        }

        $this->gearRepository->update($gear);

        if ($removedImage instanceof RemovedImage) {
            $fileSystemPath = $removedImage->getPath()->toFileSystemPath();
            if ($this->fileStorage->fileExists($fileSystemPath)) {
                $this->fileStorage->delete($fileSystemPath);
            }
        }
    }
}
