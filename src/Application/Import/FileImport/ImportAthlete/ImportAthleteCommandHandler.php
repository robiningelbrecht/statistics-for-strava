<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\ImportAthlete;

use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteBirthDate;
use App\Domain\Athlete\AthleteRepository;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;

final readonly class ImportAthleteCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteBirthDate $athleteBirthDate,
        private AthleteRepository $athleteRepository,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportAthlete);
        $athleteConfig = AppConfig::get('general.athlete');

        if (empty($athleteConfig['firstName'])) {
            throw new \RuntimeException('general.athlete.firstName configuration is missing');
        }
        if (empty($athleteConfig['lastName'])) {
            throw new \RuntimeException('general.athlete.lastName configuration is missing');
        }
        if (empty($athleteConfig['gender'])) {
            throw new \RuntimeException('general.athlete.gender configuration is missing');
        }

        try {
            $athlete = $this->athleteRepository->find();
            $athleteId = $athlete->getAthleteId();
        } catch (EntityNotFound) {
            $athleteId = $this->uuidFactory->random();
        }

        $this->athleteRepository->save(Athlete::create([
            'id' => $athleteId,
            'firstname' => $athleteConfig['firstName'],
            'lastname' => $athleteConfig['lastName'],
            'sex' => $athleteConfig['gender'],
            'birthDate' => $this->athleteBirthDate,
        ]));
    }
}
