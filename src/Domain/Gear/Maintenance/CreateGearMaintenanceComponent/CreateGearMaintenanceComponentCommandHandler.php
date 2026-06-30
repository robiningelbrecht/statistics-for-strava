<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\CreateGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\GearComponentId;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Image\ImagePath;
use App\Domain\Image\NewImage;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;

final readonly class CreateGearMaintenanceComponentCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CreateGearMaintenanceComponent);

        $imgSrc = null;
        $newImage = $command->getNewImage();
        if ($newImage instanceof NewImage) {
            $fileSystemPath = sprintf('gear-maintenance/%s.%s', $this->uuidFactory->random(), $newImage->getFilename()->getExtension());
            $this->fileStorage->write($fileSystemPath, $newImage->getContent());
            $imgSrc = ImagePath::fromFileSystemPath($fileSystemPath)->toLocalImagePath();
        }

        $gearComponent = GearComponent::create(
            id: GearComponentId::random(),
            label: $command->getLabel(),
            attachedTo: $command->getAttachedTo(),
            imgSrc: $imgSrc,
            purchasePrice: $command->getPurchasePrice(),
        );

        foreach ($command->getMaintenanceTasks() as $maintenanceTask) {
            $gearComponent->addMaintenanceTask($maintenanceTask);
        }

        $this->gearMaintenanceRepository->saveComponent($gearComponent);
    }
}
