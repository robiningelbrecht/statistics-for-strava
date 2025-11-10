<?php

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\Gears;
use App\Domain\Gear\ImportedGear\DbalImportedGearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearConfig;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\CustomGear\CustomGearBuilder;

class DbalImportedGearRepositoryTest extends ContainerTestCase
{
    private ImportedGearRepository $importedGearRepository;

    public function testFindAndSave(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->importedGearRepository->save($gear);

        $this->assertEquals(
            $gear,
            $this->importedGearRepository->find($gear->getId())
        );
    }

    public function testItShouldThrowWhenNotImportedGear(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException('Cannot save App\Domain\Gear\CustomGear\CustomGear as ImportedGear')
        );

        $this->importedGearRepository->save(CustomGearBuilder::fromDefaults()->build());
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->importedGearRepository->find(GearId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $gearOne = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->importedGearRepository->save($gearOne);
        $gearTwo = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(Meter::from(10230))
            ->build();
        $this->importedGearRepository->save($gearTwo);
        $gearThree = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(Meter::from(230))
            ->build();
        $this->importedGearRepository->save($gearThree);
        $gearFour = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(4))
            ->withDistanceInMeter(Meter::from(100230))
            ->withIsRetired(true)
            ->build();
        $this->importedGearRepository->save($gearFour);

        $this->assertEquals(
            Gears::fromArray([$gearTwo, $gearOne, $gearThree, $gearFour]),
            $this->importedGearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1000))
            ->build();
        $this->importedGearRepository->save($gear);

        $this->assertEquals(
            1000,
            $gear->getDistance()->toMeter()->toFloat()
        );

        $gear->updateDistance(Meter::from(30000));
        $this->importedGearRepository->save($gear);

        $this->assertEquals(
            30000,
            $this->importedGearRepository->find(GearId::fromUnprefixed(1))->getDistance()->toMeter()->toFloat()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importedGearRepository = new DbalImportedGearRepository(
            $this->getConnection(),
            $this->getContainer()->get(ImportedGearConfig::class),
        );
    }
}
