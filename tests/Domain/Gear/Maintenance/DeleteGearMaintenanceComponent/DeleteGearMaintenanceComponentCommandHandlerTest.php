<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\DeleteGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\DeleteGearMaintenanceComponent\DeleteGearMaintenanceComponent;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideGearMaintenanceConfig;

class DeleteGearMaintenanceComponentCommandHandlerTest extends ContainerTestCase
{
    use ProvideGearMaintenanceConfig;

    private CommandBus $commandBus;
    private KeyValueStore $keyValueStore;

    public function testItDeletesComponentAndLeavesOthersUntouched(): void
    {
        $this->importGearMaintenanceConfig();

        $this->commandBus->dispatch(DeleteGearMaintenanceComponent::fromPayload([
            'gearComponentId' => 'chain',
        ]));

        $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));

        $this->assertCount(1, $config['components']);
        $this->assertSame('di-2', $config['components'][0]['id']);
    }

    public function testItIsANoopWhenComponentDoesNotExist(): void
    {
        $this->importGearMaintenanceConfig();

        $this->commandBus->dispatch(DeleteGearMaintenanceComponent::fromPayload([
            'gearComponentId' => 'does-not-exist',
        ]));

        $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));

        $this->assertCount(2, $config['components']);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);
    }
}
