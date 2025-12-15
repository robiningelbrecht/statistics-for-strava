<?php

namespace App\Tests\Application\Import\ImportActivities;

use App\Application\Import\ImportActivities\ActivitiesToSkipDuringImport;
use App\Application\Import\ImportActivities\ActivityVisibilitiesToImport;
use App\Application\Import\ImportActivities\ImportActivities;
use App\Application\Import\ImportActivities\ImportActivitiesCommandHandler;
use App\Application\Import\ImportActivities\NumberOfNewActivitiesToProcessPerImport;
use App\Application\Import\ImportActivities\Pipeline\ActivityImportPipeline;
use App\Application\Import\ImportActivities\SkipActivitiesRecordedBefore;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityVisibility;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypesToImport;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Activity\BestEffort\ActivityBestEffortBuilder;
use App\Tests\Domain\Activity\Lap\ActivityLapBuilder;
use App\Tests\Domain\Activity\Split\ActivitySplitBuilder;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Segment\SegmentBuilder;
use App\Tests\Domain\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitiesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use provideAssertFileSystem;

    private ImportActivitiesCommandHandler $importActivitiesCommandHandler;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(9);

        $this->getContainer()->get(ImportedGearRepository::class)->save(ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromString('gear-b12659861'))
            ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withStartingCoordinate(Coordinate::createFromLatAndLng(
                    Latitude::fromString('51.2'),
                    Longitude::fromString('3.18')
                ))
                ->withTotalImageCount(0)
                ->build(),
            [
                'start_date_local' => '2024-01-01T02:58:29Z',
                'start_latlng' => [51.2, 3.18],
            ]
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot((string) $output);
        $this->assertFileSystemWrites($this->getContainer()->get('file.storage'));

        $this->assertMatchesJsonSnapshot(Json::encode(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        ));
    }

    public function testHandleWithUnexpectedError(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);
        $this->strava->triggerExceptionOnNextActivityCall();

        $this->getContainer()->get(ImportedGearRepository::class)->save(ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromString('gear-b12659861'))
            ->build()
        );

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));
        $this->assertMatchesTextSnapshot((string) $output);
    }

    public function testHandleWithActivityDelete(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1000))
                ->withStartingCoordinate(Coordinate::createFromLatAndLng(
                    Latitude::fromString('51.2'),
                    Longitude::fromString('3.18')
                ))
                ->withKudoCount(1)
                ->withName('Delete this one')
                ->build(),
            [
                'kudos_count' => 1,
                'name' => 'Delete this one',
            ]
        ));

        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1000))
            ->build();
        $this->getContainer()->get(SegmentEffortRepository::class)->add($segmentEffortOne);

        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1000))
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withKudoCount(1)
                ->withName('Delete this one as well')
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build(),
            []
        ));
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1000))
                ->withSegmentEffortId(SegmentEffortId::random())
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build()
        );
        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1000))
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build()
        );
        $this->getContainer()->get(ActivitySplitRepository::class)->add(ActivitySplitBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1001))
            ->withUnitSystem(UnitSystem::IMPERIAL)
            ->withSplitNumber(3)
            ->build());

        $this->getContainer()->get(ActivityLapRepository::class)->add(ActivityLapBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1001))
            ->build());

        $this->getContainer()->get(ActivityBestEffortRepository::class)->add(ActivityBestEffortBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1001))
            ->build());

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        $this->assertCount(
            5,
            $this->getContainer()->get(ActivityRepository::class)->findAll()->toArray()
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(SegmentEffortRepository::class)->findByActivityId(ActivityId::fromUnprefixed(1001))
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(SegmentRepository::class)->findAll(Pagination::fromOffsetAndLimit(0, 100))
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(ActivityStreamRepository::class)->findByActivityId(ActivityId::fromUnprefixed(1001))
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(ActivitySplitRepository::class)->findBy(
                ActivityId::fromUnprefixed(1001),
                UnitSystem::IMPERIAL
            )
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(ActivityLapRepository::class)->findBy(
                ActivityId::fromUnprefixed(1001),
            )
        );
        $this->assertEquals(
            0,
            $this->getConnection()->executeQuery('SELECT COUNT(*) FROM ActivityBestEffort WHERE activityId = "activity-1001"')->fetchOne()
        );
    }

    public function testHandleWithDeleteAllActivities(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(100))
                ->build(),
            []
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1000))
                ->build(),
            []
        ));

        $this->expectExceptionObject(new \RuntimeException('All activities appear to be marked for deletion. This seems like a configuration issue. Aborting to prevent data loss'));
        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));
    }

    public function testHandleWithoutActivityDelete(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWithActivityVisibilitiesToImport(): void
    {
        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            strava: $this->strava = $this->getContainer()->get(Strava::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            activityWithRawDataRepository: $this->getContainer()->get(ActivityWithRawDataRepository::class),
            numberOfNewActivitiesToProcessPerImport: $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            sportTypesToImport: $this->getContainer()->get(SportTypesToImport::class),
            activityVisibilitiesToImport: ActivityVisibilitiesToImport::from([ActivityVisibility::EVERYONE->value]),
            activitiesToSkipDuringImport: $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            skipActivitiesRecordedBefore: $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            activityImportPipeline: $this->getContainer()->get(ActivityImportPipeline::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
    }

    public function testHandleWithTooManyActivitiesToProcessInOneImport(): void
    {
        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            strava: $this->strava = $this->getContainer()->get(Strava::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            activityWithRawDataRepository: $this->getContainer()->get(ActivityWithRawDataRepository::class),
            numberOfNewActivitiesToProcessPerImport: NumberOfNewActivitiesToProcessPerImport::fromInt(1),
            sportTypesToImport: $this->getContainer()->get(SportTypesToImport::class),
            activityVisibilitiesToImport: $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            activitiesToSkipDuringImport: $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            skipActivitiesRecordedBefore: $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            activityImportPipeline: $this->getContainer()->get(ActivityImportPipeline::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        $this->assertCount(
            2,
            $this->getContainer()->get(ActivityRepository::class)->findAll()->toArray()
        );
    }

    public function testHandleWithSkipActivitiesRecordedBefore(): void
    {
        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            strava: $this->strava = $this->getContainer()->get(Strava::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            activityWithRawDataRepository: $this->getContainer()->get(ActivityWithRawDataRepository::class),
            numberOfNewActivitiesToProcessPerImport: $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            sportTypesToImport: $this->getContainer()->get(SportTypesToImport::class),
            activityVisibilitiesToImport: $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            activitiesToSkipDuringImport: $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            skipActivitiesRecordedBefore: SkipActivitiesRecordedBefore::fromOptionalString('2023-09-01'),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            activityImportPipeline: $this->getContainer()->get(ActivityImportPipeline::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWithSportTypeIsNotIncluded(): void
    {
        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            strava: $this->strava = $this->getContainer()->get(Strava::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            activityWithRawDataRepository: $this->getContainer()->get(ActivityWithRawDataRepository::class),
            numberOfNewActivitiesToProcessPerImport: $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            sportTypesToImport: SportTypesToImport::from(['Ride']),
            activityVisibilitiesToImport: $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            activitiesToSkipDuringImport: $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            skipActivitiesRecordedBefore: $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            activityImportPipeline: $this->getContainer()->get(ActivityImportPipeline::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withSportType(SportType::VIRTUAL_RIDE)
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test"}']
        );

        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            strava: $this->strava = $this->getContainer()->get(Strava::class),
            activityRepository: $this->getContainer()->get(ActivityRepository::class),
            activityWithRawDataRepository: $this->getContainer()->get(ActivityWithRawDataRepository::class),
            numberOfNewActivitiesToProcessPerImport: $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            sportTypesToImport: $this->getContainer()->get(SportTypesToImport::class),
            activityVisibilitiesToImport: $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            activitiesToSkipDuringImport: $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            skipActivitiesRecordedBefore: $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            mutex: new Mutex(
                connection: $this->getConnection(),
                clock: PausedClock::fromString('2025-12-04'),
                lockName: LockName::IMPORT_DATA_OR_BUILD_APP,
            ),
            activityImportPipeline: $this->getContainer()->get(ActivityImportPipeline::class),
        );
    }
}
