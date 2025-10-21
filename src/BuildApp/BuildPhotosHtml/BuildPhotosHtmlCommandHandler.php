<?php

declare(strict_types=1);

namespace App\BuildApp\BuildPhotosHtml;

use App\Domain\Activity\Image\ImageRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildPhotosHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ImageRepository $imageRepository,
        private SportTypeRepository $sportTypeRepository,
        private HidePhotosForSportTypes $hidePhotosForSportTypes,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildPhotosHtml);

        $importedSportTypes = $this->sportTypeRepository->findAll();
        $sportTypesToRenderPhotosFor = SportTypes::fromArray(array_filter(
            $importedSportTypes->toArray(),
            fn (SportType $sportType): bool => !$this->hidePhotosForSportTypes->has($sportType),
        ));
        $images = $this->imageRepository->findBySportTypes($sportTypesToRenderPhotosFor);

        $sportTypesWithImages = SportTypes::fromArray(array_filter(
            $sportTypesToRenderPhotosFor->toArray(),
            fn (SportType $sportType): bool => $images->hasForSportType($sportType)
        ));

        $this->buildStorage->write(
            'photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'images' => $images,
                'sportTypes' => $sportTypesWithImages,
            ]),
        );
    }
}
