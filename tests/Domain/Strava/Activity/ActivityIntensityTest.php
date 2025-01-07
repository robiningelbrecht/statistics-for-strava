<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Strava\Ftp\DbalFtpRepository;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Ftp\FtpBuilder;

class ActivityIntensityTest extends ContainerTestCase
{
    private ActivityIntensity $activityIntensity;
    private FtpRepository $ftpRepository;
    private AthleteRepository $athleteRepository;

    public function testCalculateWithFtp(): void
    {
        $ftp = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftp);

        $this->athleteRepository->save(Athlete::create([
            'birthDate' => '1989-08-14',
        ]));

        $activity = ActivityBuilder::fromDefaults()
            ->withData([
                'average_watts' => 250,
                'moving_time' => 3600,
            ])
            ->build();

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateWithHeartRate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withData([
                'average_heartrate' => 171,
                'moving_time' => 3600,
            ])
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
            ->withData([
                'moving_time' => 3600,
            ])
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

        $this->ftpRepository = new DbalFtpRepository(
            $this->getConnection()
        );
        $this->athleteRepository = new KeyValueBasedAthleteRepository(
            $this->getContainer()->get(KeyValueStore::class)
        );

        $this->activityIntensity = new ActivityIntensity(
            $this->athleteRepository,
            $this->ftpRepository
        );
    }
}
