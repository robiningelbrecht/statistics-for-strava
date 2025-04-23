<?php

namespace App\Tests\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\ImportAthleteWeight;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportAthleteWeightCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->commandBus->dispatch(new ImportAthleteWeight($output));

        $this->assertMatchesTextSnapshot((string) $output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
