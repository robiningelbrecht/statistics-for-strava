<?php

namespace App\Tests\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityVisibility;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\ImportActivities\ActivitiesToSkipDuringImport;
use App\Domain\Strava\Activity\ImportActivities\ActivityImageDownloader;
use App\Domain\Strava\Activity\ImportActivities\ActivityVisibilitiesToImport;
use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\ImportActivities\ImportActivitiesCommandHandler;
use App\Domain\Strava\Activity\ImportActivities\NumberOfNewActivitiesToProcessPerImport;
use App\Domain\Strava\Activity\ImportActivities\SkipActivitiesRecordedBefore;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\ImportedGear\ImportedGearRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Geocoding\Nominatim\Nominatim;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Split\ActivitySplitBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use App\Tests\Domain\Strava\Gear\ImportedGear\ImportedGearBuilder;
use App\Tests\Domain\Strava\Segment\SegmentBuilder;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitiesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use provideAssertFileSystem;

    private ImportActivitiesCommandHandler $importActivitiesCommandHandler;
    private SpyStrava $strava;

    public function testHandleWithNotAllGearImported(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withStartingCoordinate(Coordinate::createFromLatAndLng(
                    Latitude::fromString('51.2'),
                    Longitude::fromString('3.18')
                ))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot((string) $output);
        $this->assertFileSystemWritesAreEmpty($this->getContainer()->get('file.storage'));

        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
    }

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(9);

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('20205-01_18'),
        ));

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

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('20205-01_18'),
        ));

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

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('20205-01_18'),
        ));

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
    }

    public function testHandleWithoutActivityDelete(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('2025-01_18'),
        ));

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
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivityWithRawDataRepository::class),
            $this->getContainer()->get(GearRepository::class),
            $this->getContainer()->get(SportTypesToImport::class),
            ActivityVisibilitiesToImport::from([ActivityVisibility::EVERYONE->value]),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            $this->getContainer()->get(StravaDataImportStatus::class),
            $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            $this->getContainer()->get(ActivityImageDownloader::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('2025-01-18'),
        ));

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
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivityWithRawDataRepository::class),
            $this->getContainer()->get(GearRepository::class),
            $this->getContainer()->get(SportTypesToImport::class),
            $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            $this->getContainer()->get(StravaDataImportStatus::class),
            NumberOfNewActivitiesToProcessPerImport::fromInt(1),
            $this->getContainer()->get(ActivityImageDownloader::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('2025-01-18'),
        ));

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
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivityWithRawDataRepository::class),
            $this->getContainer()->get(GearRepository::class),
            $this->getContainer()->get(SportTypesToImport::class),
            $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            SkipActivitiesRecordedBefore::fromOptionalString('2023-09-01'),
            $this->getContainer()->get(StravaDataImportStatus::class),
            $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            $this->getContainer()->get(ActivityImageDownloader::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('2025-01_18'),
        ));

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build(), []
        ));

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivityWithRawDataRepository::class),
            $this->getContainer()->get(GearRepository::class),
            $this->getContainer()->get(SportTypesToImport::class),
            $this->getContainer()->get(ActivityVisibilitiesToImport::class),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            $this->getContainer()->get(SkipActivitiesRecordedBefore::class),
            $this->getContainer()->get(StravaDataImportStatus::class),
            $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            $this->getContainer()->get(ActivityImageDownloader::class),
        );
    }
}
