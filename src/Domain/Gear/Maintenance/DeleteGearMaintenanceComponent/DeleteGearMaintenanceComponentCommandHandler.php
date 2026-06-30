<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\DeleteGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Image\ImagePath;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class DeleteGearMaintenanceComponentCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
        private FilesystemOperator $fileStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteGearMaintenanceComponent);

        $gearComponent = $this->gearMaintenanceRepository->findComponent($command->getGearComponentId());

        $this->gearMaintenanceRepository->deleteComponent($command->getGearComponentId());

        if ($gearComponent instanceof GearComponent && null !== $localImagePath = $gearComponent->getLocalImagePath()) {
            $fileSystemPath = ImagePath::fromLocalImagePath($localImagePath)->toFileSystemPath();
            if ($this->fileStorage->fileExists($fileSystemPath)) {
                $this->fileStorage->delete($fileSystemPath);
            }
        }
    }
}
