<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\UpdateGearMaintenanceSettings;

use App\Domain\Gear\Maintenance\UpdateGearMaintenanceSettings\UpdateGearMaintenanceSettings;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideGearMaintenanceConfig;

class UpdateGearMaintenanceSettingsCommandHandlerTest extends ContainerTestCase
{
    use ProvideGearMaintenanceConfig;

    private CommandBus $commandBus;
    private KeyValueStore $keyValueStore;

    public function testItUpdatesSettingsAndPreservesComponents(): void
    {
        $this->importGearMaintenanceConfig();

        $this->commandBus->dispatch(UpdateGearMaintenanceSettings::fromPayload([
            'enabled' => 'false',
            'ignoreRetiredGear' => 'false',
        ]));

        $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));

        $this->assertFalse($config['enabled']);
        $this->assertFalse($config['ignoreRetiredGear']);
        $this->assertCount(2, $config['components']);
    }

    public function testItStoresSettingsWhenNoConfigExists(): void
    {
        $this->commandBus->dispatch(UpdateGearMaintenanceSettings::fromPayload([
            'enabled' => '1',
            'ignoreRetiredGear' => '1',
        ]));

        $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));

        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['ignoreRetiredGear']);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);
    }
}
