<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent;

use App\Domain\Gear\Maintenance\UpdateGearMaintenanceComponent\UpdateGearMaintenanceComponent;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use PHPUnit\Framework\TestCase;

class UpdateGearMaintenanceComponentTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UpdateGearMaintenanceComponent::fromPayload([
            'gearComponentId' => '  chain  ',
            'label' => 'Updated chain',
            'attachedTo' => ['b1'],
            'maintenanceTasks' => [
                ['id' => 'chain-lubed', 'label' => 'Lube', 'interval' => ['value' => 500, 'unit' => 'km']],
            ],
        ]);

        $this->assertSame('chain', (string) $command->getGearComponentId());
        $this->assertSame('Updated chain', (string) $command->getLabel());
        $this->assertCount(1, $command->getMaintenanceTasks());
    }

    public function testFromPayloadThrowsOnMissingGearComponentId(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearComponentId" is required.'));

        UpdateGearMaintenanceComponent::fromPayload([
            'label' => 'Updated chain',
            'attachedTo' => ['b1'],
            'maintenanceTasks' => [
                ['label' => 'Lube', 'interval' => ['value' => 500, 'unit' => 'km']],
            ],
        ]);
    }

    public function testFromPayloadThrowsOnEmptyGearComponentId(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearComponentId" is required.'));

        UpdateGearMaintenanceComponent::fromPayload([
            'gearComponentId' => '   ',
            'label' => 'Updated chain',
            'attachedTo' => ['b1'],
            'maintenanceTasks' => [
                ['label' => 'Lube', 'interval' => ['value' => 500, 'unit' => 'km']],
            ],
        ]);
    }
}
