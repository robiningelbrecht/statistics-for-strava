<?php

declare(strict_types=1);

namespace App\Domain\Gear\AddGear;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\GearType;
use App\Domain\Image\ImagePath;
use App\Domain\Image\NewImage;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use League\Flysystem\FilesystemOperator;
use Money\Money;

final readonly class AddGearCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
        private Clock $clock,
        private FilesystemOperator $fileStorage,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof AddGear);

        $gear = Gear::create(
            gearId: GearId::random(),
            createdOn: $this->clock->getCurrentDateTimeImmutable(),
            name: $command->getName(),
            isRetired: $command->isRetired(),
            type: GearType::CUSTOM,
        );

        if ($command->getPurchasePrice() instanceof Money) {
            $gear = $gear->withPurchasePrice($command->getPurchasePrice());
        }

        $newImage = $command->getNewImage();
        if ($newImage instanceof NewImage) {
            $fileSystemPath = sprintf('gear/%s.%s', $this->uuidFactory->random(), $newImage->getFilename()->getExtension());
            $this->fileStorage->write($fileSystemPath, $newImage->getContent());
            $gear = $gear->withLocalImagePath(ImagePath::fromFileSystemPath($fileSystemPath)->toLocalImagePath());
        }

        $this->gearRepository->add($gear);
    }
}
