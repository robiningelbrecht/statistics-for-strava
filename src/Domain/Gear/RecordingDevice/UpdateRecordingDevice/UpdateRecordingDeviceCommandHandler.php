<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice\UpdateRecordingDevice;

use App\Domain\Gear\RecordingDevice\RecordingDevice;
use App\Domain\Gear\RecordingDevice\RecordingDeviceRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class UpdateRecordingDeviceCommandHandler implements CommandHandler
{
    public function __construct(
        private RecordingDeviceRepository $recordingDeviceRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateRecordingDevice);

        $this->recordingDeviceRepository->save(
            RecordingDevice::create(
                name: $command->getName(),
                purchasePrice: $command->getPurchasePrice(),
            )
        );
    }
}
