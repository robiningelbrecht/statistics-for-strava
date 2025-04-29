<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\ImportAthlete;

use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteBirthDate;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class ImportAthleteCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private AthleteBirthDate $athleteBirthDate,
        private AthleteRepository $athleteRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportAthlete);
        $command->getOutput()->writeln('Importing athlete...');

        $athlete = $this->strava->getAthlete();
        $this->athleteRepository->save(Athlete::create([
            ...$athlete,
            'birthDate' => $this->athleteBirthDate,
        ]));
    }
}
