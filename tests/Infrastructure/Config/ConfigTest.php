<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Config;

use App\Infrastructure\Config\Config;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideGearMaintenanceConfig;

class ConfigTest extends ContainerTestCase
{
    use ProvideGearMaintenanceConfig;

    public function testLoadGearMaintenanceWhenConfigured(): void
    {
        $this->importGearMaintenanceConfig();

        $gearMaintenanceConfig = $this->getContainer()->get(Config::class)->loadGearMaintenance();

        $this->assertTrue($gearMaintenanceConfig->isFeatureEnabled());
    }

    public function testLoadGearMaintenanceReturnsDisabledDefaultWhenNotConfigured(): void
    {
        $gearMaintenanceConfig = $this->getContainer()->get(Config::class)->loadGearMaintenance();

        $this->assertFalse($gearMaintenanceConfig->isFeatureEnabled());
    }
}
