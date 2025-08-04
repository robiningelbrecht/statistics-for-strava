<?php

namespace App\Tests\Domain\Strava\Athlete\ImportAthlete;

use App\Domain\Strava\Athlete\AthleteBirthDate;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\ImportAthlete\ImportAthlete;
use App\Domain\Strava\Athlete\ImportAthlete\ImportAthleteCommandHandler;
use App\Domain\Strava\Strava;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportAthleteCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ImportAthleteCommandHandler $importAthleteCommandHandler;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(100);
        $output = new SpyOutput();

        $this->importAthleteCommandHandler->handle(new ImportAthlete($output));
        $this->assertMatchesTextSnapshot($output);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importAthleteCommandHandler = new ImportAthleteCommandHandler(
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(AthleteBirthDate::class),
            $this->getContainer()->get(AthleteRepository::class)
        );
    }
}
