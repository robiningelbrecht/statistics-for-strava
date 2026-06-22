<?php

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypes;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Gear\Gears;
use App\Domain\Gear\ImportedGear\DbalImportedGearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Gear\CustomGear\CustomGearBuilder;
use Money\Money;

class DbalImportedGearRepositoryTest extends ContainerTestCase
{
    private ImportedGearRepository $importedGearRepository;

    public function testFindAndSave(): void
    {
        $activityRepository = $this->getContainer()->get(ActivityRepository::class);

        // The stored column value (1230) must be ignored: distance is derived from activities.
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->importedGearRepository->save($gear);

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed(1))
                ->withDistance(Kilometer::from(5))
                ->build(),
            []
        ));

        $this->assertEquals(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed(1))
                ->withDistanceInMeter(Meter::from(5000))
                ->build(),
            $this->importedGearRepository->find($gear->getId())
        );
    }

    public function testItShouldPersistAndReadPurchasePrice(): void
    {
        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withPurchasePrice(Money::EUR(150000))
            ->build();
        $this->importedGearRepository->save($gear);

        $this->assertEquals(
            Money::EUR(150000),
            $this->importedGearRepository->find($gear->getId())->getPurchasePrice()
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
        $activityRepository = $this->getContainer()->get(ActivityRepository::class);

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

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed(1))
                ->withDistance(Kilometer::from(10))
                ->withElevation(Meter::from(100))
                ->withMovingTimeInSeconds(3600)
                ->build(),
            []
        ));
        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withGearId(GearId::fromUnprefixed(1))
                ->withDistance(Kilometer::from(20))
                ->withElevation(Meter::from(200))
                ->withMovingTimeInSeconds(7200)
                ->build(),
            []
        ));
        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withGearId(GearId::fromUnprefixed(2))
                ->withDistance(Kilometer::from(5))
                ->withElevation(Meter::from(50))
                ->withMovingTimeInSeconds(1800)
                ->build(),
            []
        ));

        $result = $this->importedGearRepository->findAll();

        $this->assertEquals(
            Gears::fromArray([
                ImportedGearBuilder::fromDefaults()
                    ->withGearId(GearId::fromUnprefixed(1))
                    ->withDistanceInMeter(Meter::from(30000))
                    ->withMovingTime(Seconds::from(10800))
                    ->withElevation(Meter::from(300))
                    ->withNumberOfActivities(2)
                    ->build()
                    ->withActivityTypes(ActivityTypes::fromArray([ActivityType::RIDE])),
                ImportedGearBuilder::fromDefaults()
                    ->withGearId(GearId::fromUnprefixed(2))
                    ->withDistanceInMeter(Meter::from(5000))
                    ->withMovingTime(Seconds::from(1800))
                    ->withElevation(Meter::from(50))
                    ->withNumberOfActivities(1)
                    ->build()
                    ->withActivityTypes(ActivityTypes::fromArray([ActivityType::RIDE])),
                ImportedGearBuilder::fromDefaults()
                    ->withGearId(GearId::fromUnprefixed(3))
                    ->withDistanceInMeter(Meter::zero())
                    ->build(),
                ImportedGearBuilder::fromDefaults()
                    ->withGearId(GearId::fromUnprefixed(4))
                    ->withDistanceInMeter(Meter::zero())
                    ->withIsRetired(true)
                    ->build(),
            ]),
            $result
        );
    }

    public function testUpdate(): void
    {
        $activityRepository = $this->getContainer()->get(ActivityRepository::class);

        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1000))
            ->build();
        $this->importedGearRepository->save($gear);

        // Without linked activities the derived distance is zero, regardless of the stored column.
        $this->assertEquals(
            0,
            $this->importedGearRepository->find(GearId::fromUnprefixed(1))->getDistance()->toMeter()->toFloat()
        );

        $activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withGearId(GearId::fromUnprefixed(1))
                ->withDistance(Kilometer::from(30))
                ->build(),
            []
        ));

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
        );
    }
}
