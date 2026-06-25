<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\RecordingDevice\UpdateRecordingDevice;

use App\Domain\Gear\RecordingDevice\UpdateRecordingDevice\UpdateRecordingDevice;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;

class UpdateRecordingDeviceCommandHandlerTest extends ContainerTestCase
{
    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->commandBus->dispatch(UpdateRecordingDevice::fromPayload([
            'name' => 'Garmin Edge 530',
            'purchasePriceAmount' => '299.50',
            'purchasePriceCurrency' => 'EUR',
        ]));

        $this->assertEquals(
            [[
                'id' => 'recordingDevice-garmin-edge-530',
                'name' => 'Garmin Edge 530',
                'purchasePriceAmount' => 29950,
                'purchasePriceCurrency' => 'EUR',
            ]],
            $this->getConnection()->fetchAllAssociative(
                'SELECT id, name, purchasePriceAmount, purchasePriceCurrency FROM RecordingDevice'
            ),
        );
    }

    public function testHandleWithoutPurchasePrice(): void
    {
        $this->commandBus->dispatch(UpdateRecordingDevice::fromPayload([
            'name' => 'Garmin Edge 530',
        ]));

        $this->assertEquals(
            [[
                'id' => 'recordingDevice-garmin-edge-530',
                'name' => 'Garmin Edge 530',
                'purchasePriceAmount' => null,
                'purchasePriceCurrency' => null,
            ]],
            $this->getConnection()->fetchAllAssociative(
                'SELECT id, name, purchasePriceAmount, purchasePriceCurrency FROM RecordingDevice'
            ),
        );
    }

    public function testHandleUpdatesExistingDevice(): void
    {
        $this->commandBus->dispatch(UpdateRecordingDevice::fromPayload([
            'name' => 'Garmin Edge 530',
            'purchasePriceAmount' => '299.50',
            'purchasePriceCurrency' => 'EUR',
        ]));

        $this->commandBus->dispatch(UpdateRecordingDevice::fromPayload([
            'name' => 'Garmin Edge 530',
            'purchasePriceAmount' => '199.50',
            'purchasePriceCurrency' => 'USD',
        ]));

        $this->assertEquals(
            [[
                'id' => 'recordingDevice-garmin-edge-530',
                'name' => 'Garmin Edge 530',
                'purchasePriceAmount' => 19950,
                'purchasePriceCurrency' => 'USD',
            ]],
            $this->getConnection()->fetchAllAssociative(
                'SELECT id, name, purchasePriceAmount, purchasePriceCurrency FROM RecordingDevice'
            ),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
