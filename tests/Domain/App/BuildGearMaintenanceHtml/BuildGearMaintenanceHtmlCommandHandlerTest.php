<?php

namespace App\Tests\Domain\App\BuildGearMaintenanceHtml;

use App\Domain\App\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Domain\App\BuildGearMaintenanceHtml\BuildGearMaintenanceHtmlCommandHandler;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Gear\GearBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BuildGearMaintenanceHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('g10130856'))
            ->build();
        $this->getContainer()->get(GearRepository::class)->save($gear);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName(Name::fromString('#sfs-chain-lubed'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 01:00:00'))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 02:00:00'))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('4'))
                ->withName(Name::fromString('#sfs-chain-lubed wrong'))
                ->withGearId(GearId::random())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));

        $this->commandBus->dispatch(new BuildGearMaintenanceHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }

    public function testHandleWhenDisabled(): void
    {
        $fileStorage = $this->getContainer()->get('build.storage');

        new BuildGearMaintenanceHtmlCommandHandler(
            gearMaintenanceConfig: GearMaintenanceConfig::fromArray(''),
            maintenanceTaskTagRepository: $this->getContainer()->get(MaintenanceTaskTagRepository::class),
            gearRepository: $this->getContainer()->get(GearRepository::class),
            maintenanceTaskProgressCalculator: $this->getContainer()->get(MaintenanceTaskProgressCalculator::class),
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
