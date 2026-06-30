<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\Maintenance;

use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskId;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideGearMaintenanceConfig;

class KeyValueBasedGearMaintenanceRepositoryTest extends ContainerTestCase
{
    use ProvideGearMaintenanceConfig;

    private GearMaintenanceRepository $gearMaintenanceRepository;

    public function testFindReturnsTheConfiguredConfig(): void
    {
        $this->importGearMaintenanceConfig();

        $config = $this->gearMaintenanceRepository->find();

        $this->assertTrue($config->isFeatureEnabled());
        $this->assertTrue($config->ignoreRetiredGear());
        $this->assertCount(2, $config->getGearComponents());
    }

    public function testFindReturnsDisabledDefaultWhenNotConfigured(): void
    {
        $this->assertFalse($this->gearMaintenanceRepository->find()->isFeatureEnabled());
    }

    public function testFindMaintenanceTaskAndItsComponent(): void
    {
        $this->importGearMaintenanceConfig();

        $task = $this->gearMaintenanceRepository->findMaintenanceTask(MaintenanceTaskId::fromUnprefixed('chain-lubed'));
        $this->assertNotNull($task);
        $this->assertSame('Lube', (string) $task->getLabel());

        $component = $this->gearMaintenanceRepository->findComponentForMaintenanceTask(MaintenanceTaskId::fromUnprefixed('chain-lubed'));
        $this->assertNotNull($component);
        $this->assertSame('Some cool chain', (string) $component->getLabel());

        $this->assertNull($this->gearMaintenanceRepository->findMaintenanceTask(MaintenanceTaskId::fromUnprefixed('does-not-exist')));
        $this->assertNull($this->gearMaintenanceRepository->findComponentForMaintenanceTask(MaintenanceTaskId::fromUnprefixed('does-not-exist')));
    }

    public function testUpdateConfigFlipsSettingsAndPreservesComponents(): void
    {
        $this->importGearMaintenanceConfig();

        $this->gearMaintenanceRepository->updateConfig(isFeatureEnabled: false, ignoreRetiredGear: false);

        $config = $this->gearMaintenanceRepository->find();
        $this->assertFalse($config->isFeatureEnabled());
        $this->assertFalse($config->ignoreRetiredGear());
        $this->assertCount(2, $config->getGearComponents());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->gearMaintenanceRepository = $this->getContainer()->get(GearMaintenanceRepository::class);
    }
}
