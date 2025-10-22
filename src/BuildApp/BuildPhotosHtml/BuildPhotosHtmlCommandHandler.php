<?php

declare(strict_types=1);

namespace App\BuildApp\BuildPhotosHtml;

use App\BuildApp\Countries;
use App\Domain\Activity\Image\ImageRepository;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildPhotosHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ImageRepository $imageRepository,
        private SportTypeRepository $sportTypeRepository,
        private Countries $countries,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildPhotosHtml);

        $images = $this->imageRepository->findAll();

        $this->buildStorage->write(
            'photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'images' => $images,
                'sportTypes' => $this->sportTypeRepository->findForImages(),
                'countries' => $this->countries->getUsedInPhotos(),
                'totalPhotoCount' => count($images),
            ]),
        );
    }
}
