<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\DailyTrainingLoad;
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

class DailyTrainingLoadTest extends ContainerTestCase
{
    private DailyTrainingLoad $dailyTrainingLoad;
    private AthleteRepository $athleteRepository;
    private FtpHistory $ftpHistory;

    public function testCalculateWithPowerBasedData(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAveragePower(250)
            ->withMovingTimeInSeconds(3600)
            ->withSportType(SportType::RIDE)
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

        $this->assertEquals(
            100,
            $this->dailyTrainingLoad->calculate(SerializableDateTime::fromString('2023-10-10')),
        );
    }

    public function testCalculateWhenFtpNotFound(): void
    {
        $this->dailyTrainingLoad = new DailyTrainingLoad(
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivitiesEnricher::class),
            new ActivityIntensity(
                $this->getContainer()->get(ActivitiesEnricher::class),
                $this->athleteRepository = new KeyValueBasedAthleteRepository(
                    $this->getContainer()->get(KeyValueStore::class),
                    $this->getContainer()->get(MaxHeartRateFormula::class),
                    $this->getContainer()->get(RestingHeartRateFormula::class),
                ),
                $this->ftpHistory = FtpHistory::fromArray([]),
            ),
            $this->ftpHistory = FtpHistory::fromArray([]),
            $this->athleteRepository = new KeyValueBasedAthleteRepository(
                $this->getContainer()->get(KeyValueStore::class),
                $this->getContainer()->get(MaxHeartRateFormula::class),
                $this->getContainer()->get(RestingHeartRateFormula::class),
            )
        );

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withAveragePower(250)
            ->withAverageHeartRate(171)
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

        $this->assertEquals(
            277,
            $this->dailyTrainingLoad->calculate(SerializableDateTime::fromString('2023-10-10')),
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
            277,
            $this->dailyTrainingLoad->calculate(SerializableDateTime::fromString('2023-10-10')),
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
            $this->dailyTrainingLoad->calculate(SerializableDateTime::fromString('2023-10-10')),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dailyTrainingLoad = new DailyTrainingLoad(
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(ActivitiesEnricher::class),
            $this->getContainer()->get(ActivityIntensity::class),
            $this->ftpHistory = FtpHistory::fromArray(['2023-04-01' => 250]),
            $this->athleteRepository = new KeyValueBasedAthleteRepository(
                $this->getContainer()->get(KeyValueStore::class),
                $this->getContainer()->get(MaxHeartRateFormula::class),
                $this->getContainer()->get(RestingHeartRateFormula::class),
            )
        );
    }
}
