<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\CouldNotDetermineActivityIntensity;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Athlete\RestingHeartRate\RestingHeartRateFormula;
use App\Domain\Ftp\FtpHistory;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\Stream\ActivityStreamBuilder;

class ActivityIntensityTest extends ContainerTestCase
{
    private ActivityIntensity $activityIntensity;
    private AthleteRepository $athleteRepository;
    private FtpHistory $ftpHistory;

    public function testCalculateWithPower(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(250)
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activity->getId())
                ->withStreamType(StreamType::WATTS)
                ->withNormalizedPower(250)
                ->build()
        );

        $this->assertEmpty(ActivityIntensity::$cachedIntensities);
        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity->getId()),
        );
        $this->assertArrayHasKey(
            (string) $activity->getId(),
            ActivityIntensity::$cachedIntensities
        );
        $this->assertEquals(
            100,
            $this->activityIntensity->calculatePowerBased($activity->getId()),
        );
    }

    public function testCalculateWithPowerWhenEmptyNormalizedPower(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(250)
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));

        $this->expectExceptionObject(new CouldNotDetermineActivityIntensity('Activity has no normalized power'));
        $this->activityIntensity->calculatePowerBased($activity->getId());
    }

    public function testCalculateWithPowerWhenActivityIsNotARide(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withSportType(SportType::RUN)
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));

        $this->expectExceptionObject(new CouldNotDetermineActivityIntensity('Activity is not a ride'));
        $this->activityIntensity->calculatePowerBased($activity->getId());
    }

    public function testCalculateWithPowerWhenFtpNotFound(): void
    {
        $this->activityIntensity = new ActivityIntensity(
            $this->getContainer()->get(EnrichedActivities::class),
            $this->athleteRepository = new KeyValueBasedAthleteRepository(
                $this->getContainer()->get(KeyValueStore::class),
                $this->getContainer()->get(MaxHeartRateFormula::class),
                $this->getContainer()->get(RestingHeartRateFormula::class),
            ),
            $this->ftpHistory = FtpHistory::fromArray([]),
        );

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(250)
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId($activity->getId())
                ->withStreamType(StreamType::WATTS)
                ->withNormalizedPower(250)
                ->build()
        );

        $this->expectExceptionObject(new CouldNotDetermineActivityIntensity('Ftp not found'));
        $this->activityIntensity->calculatePowerBased($activity->getId());
    }

    public function testCalculateWithHeartRate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(171)
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertEmpty(ActivityIntensity::$cachedIntensities);
        $this->assertEquals(
            87,
            $this->activityIntensity->calculateHeartRateBased($activity->getId()),
        );
        $this->assertArrayHasKey(
            (string) $activity->getId(),
            ActivityIntensity::$cachedIntensities
        );
        $this->assertEquals(
            87,
            $this->activityIntensity->calculateHeartRateBased($activity->getId()),
        );
    }

    public function testCalculateWithoutAnyData(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->withAverageHeartRate(0)
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertEquals(
            0,
            $this->activityIntensity->calculate($activity->getId()),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityIntensity = new ActivityIntensity(
            $this->getContainer()->get(EnrichedActivities::class),
            $this->athleteRepository = new KeyValueBasedAthleteRepository(
                $this->getContainer()->get(KeyValueStore::class),
                $this->getContainer()->get(MaxHeartRateFormula::class),
                $this->getContainer()->get(RestingHeartRateFormula::class),
            ),
            $this->ftpHistory = FtpHistory::fromArray(['2023-04-01' => 250]),
        );
    }
}
