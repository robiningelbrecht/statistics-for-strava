<?php

declare(strict_types=1);

namespace App\Application\Build\BuildManifest;

use App\Application\AppName;
use App\Application\AppUrl;
use App\Domain\Athlete\AthleteRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use League\Flysystem\FilesystemOperator;

final readonly class BuildManifestCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private AppUrl $appUrl,
        private KernelProjectDir $kernelProjectDir,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildManifest);

        $athlete = $this->athleteRepository->find();

        $manifest = file_get_contents($this->kernelProjectDir.'/templates/manifest.json');
        assert(is_string($manifest));
        $manifest = str_replace('[APP_NAME]', sprintf('%s | %s', AppName::LABEL, $athlete->getName()), $manifest);
        $manifest = str_replace('[APP_HOST]', (string) $this->appUrl, $manifest);
        $manifest = str_replace('[APP_BASE_PATH]', $this->appUrl->getBasePath() ?? '', $manifest);

        $this->buildStorage->write('manifest.json', $manifest);
    }
}
