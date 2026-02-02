<?php

namespace App\Tests\Domain\Gear\CustomGear;

use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\CustomGear\DbalCustomGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\Gears;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\Tag;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class DbalCustomGearRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CustomGearRepository $customGearRepository;

    public function testSave(): void
    {
        $gear = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->customGearRepository->save($gear);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM GEAR')->fetchAllAssociative()
        );
    }

    public function testFindAll(): void
    {
        $gearOne = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->customGearRepository->save($gearOne);
        $gearTwo = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(Meter::from(10230))
            ->build();
        $this->customGearRepository->save($gearTwo);
        $gearThree = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(Meter::from(230))
            ->build();
        $this->customGearRepository->save($gearThree);
        $gearFour = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(4))
            ->withDistanceInMeter(Meter::from(100230))
            ->withIsRetired(true)
            ->build();
        $this->customGearRepository->save($gearFour);

        $this->assertEquals(
            Gears::fromArray([
                $gearTwo->withFullTag(Tag::fromString('#sfs-2')),
                $gearOne->withFullTag(Tag::fromString('#sfs-1')),
                $gearThree->withFullTag(Tag::fromString('#sfs-3')),
                $gearFour->withFullTag(Tag::fromString('#sfs-4')),
            ]),
            $this->customGearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1000))
            ->build();
        $this->customGearRepository->save($gear);

        $this->assertEquals(
            1000,
            $gear->getDistance()->toMeter()->toFloat()
        );

        $this->customGearRepository->save($gear->withDistance(Meter::from(30000)));

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM GEAR')->fetchAllAssociative()
        );
    }

    public function testRemoveAll(): void
    {
        $gearOne = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->customGearRepository->save($gearOne);
        $gearTwo = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(Meter::from(10230))
            ->build();
        $this->customGearRepository->save($gearTwo);
        $gearThree = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(Meter::from(230))
            ->build();
        $this->getContainer()->get(ImportedGearRepository::class)->save($gearThree);

        $this->customGearRepository->removeAll();

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM GEAR')->fetchAllAssociative()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->customGearRepository = new DbalCustomGearRepository(
            $this->getConnection(),
            $this->getContainer()->get(CustomGearConfig::class)
        );
    }
}
