<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Image\ImagePath;
use App\Domain\Image\NewImage;
use App\Domain\Image\RemovedImage;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;

final readonly class UpdateGearMaintenanceComponentCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGearMaintenanceComponent);

        // Keep the existing image unless the dropzone explicitly added or removed one.
        $existingComponent = $this->gearMaintenanceRepository->findComponent($command->getGearComponentId());
        $imgSrc = $existingComponent?->getLocalImagePath();

        $newImage = $command->getNewImage();
        $removedImage = $command->getRemovedImage();

        if ($newImage instanceof NewImage) {
            $fileSystemPath = sprintf('gear-maintenance/%s.%s', $this->uuidFactory->random(), $newImage->getFilename()->getExtension());
            $this->fileStorage->write($fileSystemPath, $newImage->getContent());
            $imgSrc = ImagePath::fromFileSystemPath($fileSystemPath)->toLocalImagePath();
        } elseif ($removedImage instanceof RemovedImage) {
            $imgSrc = null;
        }

        $gearComponent = GearComponent::create(
            id: $command->getGearComponentId(),
            label: $command->getLabel(),
            attachedTo: $command->getAttachedTo(),
            imgSrc: $imgSrc,
            purchasePrice: $command->getPurchasePrice(),
        );

        foreach ($command->getMaintenanceTasks() as $maintenanceTask) {
            $gearComponent->addMaintenanceTask($maintenanceTask);
        }

        $this->gearMaintenanceRepository->saveComponent($gearComponent);

        if ($removedImage instanceof RemovedImage) {
            $fileSystemPath = $removedImage->getPath()->toFileSystemPath();
            if ($this->fileStorage->fileExists($fileSystemPath)) {
                $this->fileStorage->delete($fileSystemPath);
            }
        }
    }
}
