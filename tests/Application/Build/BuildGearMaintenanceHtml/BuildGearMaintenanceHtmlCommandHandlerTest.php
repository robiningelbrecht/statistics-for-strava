<?php

namespace App\Tests\Application\Build\BuildGearMaintenanceHtml;

use App\Application\Build\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Application\Build\BuildGearMaintenanceHtml\BuildGearMaintenanceHtmlCommandHandler;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Application\BuildAppFilesTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BuildGearMaintenanceHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('g10130856'))
            ->build();
        $this->getContainer()->get(ImportedGearRepository::class)->save($gear);

        $this->getContainer()->get(
            ImportedGearRepository::class)->save(ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('retired'))
            ->withIsRetired(true)
            ->build()
            );

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName(Name::fromString('#sfs-chain-lubed'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('10'))
                ->withName(Name::fromString('#sfs-chain-lubed'))
                ->withGearId(GearId::fromUnprefixed('retired'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 00:00:00'))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 01:00:00'))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId($gear->getId())
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01 02:00:00'))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
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
            gearMaintenanceConfig: GearMaintenanceConfig::fromArray([]),
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
