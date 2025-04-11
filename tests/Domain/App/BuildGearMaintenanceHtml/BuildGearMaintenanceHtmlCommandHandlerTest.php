<?php

namespace App\Tests\Domain\App\BuildGearMaintenanceHtml;

use App\Domain\App\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Domain\App\BuildGearMaintenanceHtml\BuildGearMaintenanceHtmlCommandHandler;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BuildGearMaintenanceHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildGearMaintenanceHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }

    public function testHandleWhenDisabled(): void
    {
        $fileStorage = $this->getContainer()->get('build.storage');

        new BuildGearMaintenanceHtmlCommandHandler(
            gearMaintenanceConfig: GearMaintenanceConfig::fromYmlString(''),
            maintenanceTaskTagRepository: $this->getContainer()->get(MaintenanceTaskTagRepository::class),
            gearRepository: $this->getContainer()->get(GearRepository::class),
            gearMaintenanceStorage: $fileStorage,
            twig: $this->getContainer()->get(Environment::class),
            buildStorage: $fileStorage,
            translator: $this->getContainer()->get(TranslatorInterface::class),
        )->handle(
            new BuildGearMaintenanceHtml()
        );
        $this->assertFileSystemWrites($fileStorage);
    }
}
