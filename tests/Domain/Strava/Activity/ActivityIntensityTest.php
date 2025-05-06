<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Strava\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Strava\Ftp\FtpHistory;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
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
            ->build();

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateWithHeartRate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withAverageHeartRate(171)
            ->withMovingTimeInSeconds(3600)
            ->build();

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateShouldBeNull(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withMovingTimeInSeconds(3600)
            ->build();

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $this->assertNull(
            $this->activityIntensity->calculate($activity),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpHistory = FtpHistory::fromString(Json::encode(['2023-04-01' => 250]));
        $this->athleteRepository = new KeyValueBasedAthleteRepository(
            $this->getContainer()->get(KeyValueStore::class),
            $this->getContainer()->get(MaxHeartRateFormula::class),
        );

        $this->activityIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpHistory
        );
    }
}
