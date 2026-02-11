<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\DbalActivityIdRepository;
use App\Domain\Activity\DbalActivityWithRawDataRepository;
use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\CustomGear\CustomGearBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityIdRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ActivityIdRepository $activityIdRepository;
    private GearRepository $gearRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            ActivityIds::fromArray([$activityOne->getId(), $activityTwo->getId(), $activityThree->getId()]),
            $this->activityIdRepository->findAll()
        );
    }

    public function testFindAllWithoutStravaGear(): void
    {
        $this->getContainer()->get(ImportedGearRepository::class)->save(
            ImportedGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('imported'))
                ->build()
        );
        $this->getContainer()->get(CustomGearRepository::class)->save(
            CustomGearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('custom'))
                ->build()
        );
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withoutGearId()
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->withGearId(GearId::fromUnprefixed('imported'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->withGearId(GearId::fromUnprefixed('custom'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            ActivityIds::fromArray([$activityOne->getId(), $activityThree->getId()]),
            $this->activityIdRepository->findAllWithoutStravaGear()
        );
    }

    public function testCount(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            3,
            $this->activityIdRepository->count()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityIdRepository = new DbalActivityIdRepository(
            $this->getConnection(),
        );
        $this->activityWithRawDataRepository = new DbalActivityWithRawDataRepository(
            $this->getConnection(),
            $this->getContainer()->get(ActivityRepository::class),
        );
    }
}
