<?php

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\DbalGearRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Gears;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\ContainerTestCase;

class DbalGearRepositoryTest extends ContainerTestCase
{
    private GearRepository $gearRepository;

    public function testFindAndSave(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->gearRepository->save($gear);

        $this->assertEquals(
            $gear,
            $this->gearRepository->find($gear->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->gearRepository->find(GearId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $gearOne = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->gearRepository->save($gearOne);
        $gearTwo = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(Meter::from(10230))
            ->build();
        $this->gearRepository->save($gearTwo);
        $gearThree = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(Meter::from(230))
            ->build();
        $this->gearRepository->save($gearThree);
        $gearFour = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(4))
            ->withDistanceInMeter(Meter::from(100230))
            ->withIsRetired(true)
            ->build();
        $this->gearRepository->save($gearFour);

        $this->assertEquals(
            Gears::fromArray([$gearTwo, $gearOne, $gearThree, $gearFour]),
            $this->gearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1000))
            ->build();
        $this->gearRepository->save($gear);

        $this->assertEquals(
            1000,
            $gear->getDistance()->toMeter()->toFloat()
        );

        $gear->updateDistance(Meter::from(30000));
        $this->gearRepository->save($gear);

        $this->assertEquals(
            30000,
            $this->gearRepository->find(GearId::fromUnprefixed(1))->getDistance()->toMeter()->toFloat()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->gearRepository = new DbalGearRepository(
            $this->getConnection()
        );
    }
}
