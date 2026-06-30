<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent\UpdateGearMaintenanceComponent;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideGearMaintenanceConfig;

class UpdateGearMaintenanceComponentCommandHandlerTest extends ContainerTestCase
{
    use ProvideGearMaintenanceConfig;

    private CommandBus $commandBus;
    private KeyValueStore $keyValueStore;

    public function testItUpdatesExistingComponentAndLeavesOthersUntouched(): void
    {
        $this->importGearMaintenanceConfig();

        $this->commandBus->dispatch(UpdateGearMaintenanceComponent::fromPayload([
            'gearComponentId' => 'chain',
            'label' => 'Updated chain',
            'attachedTo' => ['b9'],
            'maintenanceTasks' => [
                ['id' => 'chain-lubed', 'label' => 'Lube', 'interval' => ['value' => 600, 'unit' => 'km']],
            ],
        ]));

        $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));

        $this->assertCount(2, $config['components']);

        $chain = $this->findComponentById($config['components'], 'chain');
        $this->assertNotNull($chain);
        $this->assertSame('Updated chain', $chain['label']);
        $this->assertSame(['b9'], $chain['attachedTo']);
        $this->assertCount(1, $chain['maintenance']);
        $this->assertSame('chain-lubed', $chain['maintenance'][0]['id']);
        $this->assertSame(600, $chain['maintenance'][0]['interval']['value']);

        $di2 = $this->findComponentById($config['components'], 'di-2');
        $this->assertNotNull($di2);
        $this->assertSame('DI2 Battery', $di2['label']);
    }

    /**
     * @param array<int, array<string, mixed>> $components
     *
     * @return array<string, mixed>|null
     */
    private function findComponentById(array $components, string $id): ?array
    {
        foreach ($components as $component) {
            if (($component['id'] ?? null) === $id) {
                return $component;
            }
        }

        return null;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->keyValueStore = $this->getContainer()->get(KeyValueStore::class);
    }
}
