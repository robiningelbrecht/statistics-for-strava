<?php

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\CustomGear\CustomGearRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Gear\CustomGear\CustomGearBuilder;
use App\Tests\Domain\Strava\Gear\ImportedGear\ImportedGearBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class CombiningGearRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testFindAll(): void
    {
        $gearOne = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(Meter::from(1230))
            ->build();
        $this->getContainer()->get(ImportedGearRepository::class)->save($gearOne);
        $gearTwo = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(Meter::from(10230))
            ->build();
        $this->getContainer()->get(CustomGearRepository::class)->save($gearTwo);
        $gearThree = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(Meter::from(230))
            ->build();
        $this->getContainer()->get(ImportedGearRepository::class)->save($gearThree);
        $gearFour = CustomGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(4))
            ->withDistanceInMeter(Meter::from(100230))
            ->withIsRetired(true)
            ->build();
        $this->getContainer()->get(CustomGearRepository::class)->save($gearFour);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM GEAR')->fetchAllAssociative()
        );
    }
}
