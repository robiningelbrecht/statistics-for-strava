<?php

declare(strict_types=1);

namespace App\Domain\Manifest\BuildManifest;

use App\Domain\Manifest\ManifestAppUrl;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class BuildManifestCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ManifestAppUrl $manifestAppUrl,
        private FilesystemOperator $publicStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildManifest);

        $athlete = $this->athleteRepository->find();

        $manifest = $this->publicStorage->read('manifest.json');
        $manifest = str_replace('[APP_NAME]', sprintf('Strava Statistics | %s', $athlete->getName()), $manifest);
        $manifest = str_replace('[APP_HOST]', (string) $this->manifestAppUrl, $manifest);

        $this->publicStorage->write('manifest.json', $manifest);
    }
}
