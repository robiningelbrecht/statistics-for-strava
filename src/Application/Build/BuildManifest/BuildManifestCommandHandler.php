<?php

declare(strict_types=1);

namespace App\Application\Build\BuildManifest;

use App\Application\AppUrl;
use App\Domain\Athlete\AthleteRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class BuildManifestCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private AppUrl $appUrl,
        private FilesystemOperator $publicStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildManifest);

        $athlete = $this->athleteRepository->find();

        $manifest = $this->publicStorage->read('manifest.json');
        $manifest = str_replace('[APP_NAME]', sprintf('Statistics for Strava | %s', $athlete->getName()), $manifest);
        $manifest = str_replace('[APP_HOST]', (string) $this->appUrl, $manifest);
        $manifest = str_replace('[APP_BASE_PATH]', $this->appUrl->getBasePath() ?? '', $manifest);

        $this->publicStorage->write('manifest.json', $manifest);
    }
}
