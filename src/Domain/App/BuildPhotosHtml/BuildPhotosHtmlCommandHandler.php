<?php

declare(strict_types=1);

namespace App\Domain\App\BuildPhotosHtml;

use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildPhotosHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ImageRepository $imageRepository,
        private SportTypeRepository $sportTypeRepository,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildPhotosHtml);

        $importedSportTypes = $this->sportTypeRepository->findAll();
        $images = $this->imageRepository->findAll();

        $this->buildStorage->write(
            'photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'images' => $images,
                'sportTypes' => $importedSportTypes,
            ]),
        );
    }
}
