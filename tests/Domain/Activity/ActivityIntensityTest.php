<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Ftp\FtpHistory;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class ActivityIntensityTest extends ContainerTestCase
{
    private ActivityIntensity $activityIntensity;
    private FtpHistory $ftpHistory;
    private AthleteRepository $athleteRepository;

    public function testCalculateWithFtp(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAveragePower(250)
            ->withMovingTimeInSeconds(3600)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10'))
            ->build();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            $activity,
            []
        ));

        $this->assertEquals(
            100,
            $this->activityIntensity->calculateForDate(SerializableDateTime::fromString('2023-10-10')),
        );
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

        $this->assertEquals(
            100,
            $this->activityIntensity->calculateForDate(SerializableDateTime::fromString('2023-10-10')),
        );
    }

    public function testCalculateShouldBeZero(): void
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
            $this->activityIntensity->calculateForDate(SerializableDateTime::fromString('2023-10-10')),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpHistory = FtpHistory::fromArray(['2023-04-01' => 250]);
        $this->athleteRepository = new KeyValueBasedAthleteRepository(
            $this->getContainer()->get(KeyValueStore::class),
            $this->getContainer()->get(MaxHeartRateFormula::class),
        );

        $this->activityIntensity = new ActivityIntensity(
            $this->getContainer()->get(ActivityRepository::class),
            $this->athleteRepository,
            $this->ftpHistory
        );
    }
}
