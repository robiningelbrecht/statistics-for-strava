<?php

namespace App\Tests\Application\Import\ImportChallenges;

use App\Application\Import\ImportChallenges\ImportChallenges;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Challenge\ChallengeId;
use App\Domain\Challenge\ChallengeRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Challenge\ChallengeBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportChallengesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use provideAssertFileSystem;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(3);

        $this->getContainer()->get(ChallengeRepository::class)->add(
            ChallengeBuilder::fromDefaults()
                ->withChallengeId(ChallengeId::fromUnprefixed('2023-10_challenge_2'))
                ->build()
        );

        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
        ]));

        $this->commandBus->dispatch(new ImportChallenges($output));

        $this->assertMatchesTextSnapshot($output);
        $this->assertFileSystemWrites($this->getContainer()->get('file.storage'));
    }

    public function testHandleWhenErrorInDownload(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(100);
        $this->strava->triggerExceptionOnNextCall();

        $this->getContainer()->get(ChallengeRepository::class)->add(
            ChallengeBuilder::fromDefaults()
                ->withChallengeId(ChallengeId::fromUnprefixed('2023-10_challenge_2'))
                ->build()
        );

        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
        ]));

        $this->commandBus->dispatch(new ImportChallenges($output));

        $this->assertMatchesTextSnapshot($output);
        $this->assertFileSystemWrites($this->getContainer()->get('file.storage'));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
