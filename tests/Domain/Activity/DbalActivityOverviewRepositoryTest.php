<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Domain\Activity\ActivityOverviewItem;
use App\Domain\Activity\ActivityOverviewRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\DbalActivityOverviewRepository;
use App\Domain\Activity\DbalActivityRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\DbalGearRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\GearBuilder;
use PHPUnit\Framework\Attributes\DataProvider;

class DbalActivityOverviewRepositoryTest extends ContainerTestCase
{
    private ActivityOverviewRepository $activityOverviewRepository;
    private ActivityRepository $activityRepository;
    private GearRepository $gearRepository;

    public function testFindMapsRowToOverviewItem(): void
    {
        $this->gearRepository->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('99'))
                ->withName('Trail Shoes')
                ->build()
        );

        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('42'))
                ->withName('Morning Run')
                ->withSportType(SportType::RUN)
                ->withStartDateTime(SerializableDateTime::fromString('2026-06-04 10:00:00'))
                ->withGearId(GearId::fromUnprefixed('99'))
                ->withDeviceName('Garmin Forerunner')
                ->withIsCommute(true)
                ->withTotalImageCount(3)
                ->build(),
            ['raw' => 'data'],
        ));

        $overview = $this->activityOverviewRepository->find(Pagination::fromOffsetAndLimit(0, 10));

        $this->assertEquals(
            [
                ActivityOverviewItem::fromState(
                    activityId: ActivityId::fromUnprefixed('42'),
                    name: ActivityName::fromString('Morning Run'),
                    sportType: SportType::RUN,
                    startDate: SerializableDateTime::fromString('2026-06-04 10:00:00'),
                    gearName: 'Trail Shoes',
                    deviceName: 'Garmin Forerunner',
                    isCommute: true,
                    totalImageCount: 3,
                ),
            ],
            $overview->getItems()
        );
        $this->assertEquals(1, $overview->getTotal());
    }

    #[DataProvider('providePaginationScenarios')]
    public function testFindOrdersByStartDateDescAndPaginates(
        Pagination $pagination,
        array $expectedNames,
        int $expectedTotal,
    ): void {
        $this->seedThreeActivities();

        $overview = $this->activityOverviewRepository->find($pagination);

        $this->assertSame(
            $expectedNames,
            array_map(
                static fn (ActivityOverviewItem $item): string => (string) $item->getName(),
                $overview->getItems()
            )
        );
        $this->assertEquals($expectedTotal, $overview->getTotal());
        $this->assertEquals($pagination, $overview->getPagination());
    }

    public static function providePaginationScenarios(): iterable
    {
        yield 'first page is ordered most recent first' => [
            Pagination::fromOffsetAndLimit(0, 2),
            ['Newest', 'Middle'],
            3,
        ];

        yield 'second page returns the remainder while total stays the same' => [
            Pagination::fromOffsetAndLimit(2, 2),
            ['Oldest'],
            3,
        ];

        yield 'a single page can hold everything' => [
            Pagination::fromOffsetAndLimit(0, 10),
            ['Newest', 'Middle', 'Oldest'],
            3,
        ];

        yield 'an offset past the end yields no items but still reports the total' => [
            Pagination::fromOffsetAndLimit(10, 10),
            [],
            3,
        ];
    }

    public function testFindReturnsAnEmptyOverviewWhenThereIsNoData(): void
    {
        $overview = $this->activityOverviewRepository->find(Pagination::fromOffsetAndLimit(0, 10));

        $this->assertTrue($overview->isEmpty());
        $this->assertSame([], $overview->getItems());
        $this->assertEquals(0, $overview->getTotal());
    }

    private function seedThreeActivities(): void
    {
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Oldest')
                ->withStartDateTime(SerializableDateTime::fromString('2026-06-01 08:00:00'))
                ->build(),
            [],
        ));
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withName('Middle')
                ->withStartDateTime(SerializableDateTime::fromString('2026-06-02 08:00:00'))
                ->build(),
            [],
        ));
        $this->activityRepository->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withName('Newest')
                ->withStartDateTime(SerializableDateTime::fromString('2026-06-03 08:00:00'))
                ->build(),
            [],
        ));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnection()
        );
        $this->activityOverviewRepository = new DbalActivityOverviewRepository(
            $this->getConnection()
        );
        $this->gearRepository = new DbalGearRepository(
            $this->getConnection()
        );
    }
}
