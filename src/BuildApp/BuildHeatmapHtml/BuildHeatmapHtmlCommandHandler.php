<?php

declare(strict_types=1);

namespace App\BuildApp\BuildHeatmapHtml;

use App\Domain\Activity\Route\Route;
use App\Domain\Activity\Route\RouteRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildHeatmapHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private RouteRepository $routeRepository,
        private SportTypeRepository $sportTypeRepository,
        private HeatmapConfig $heatmapConfig,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildHeatmapHtml);

        $importedSportTypes = $this->sportTypeRepository->findAll();
        $routes = $this->routeRepository->findAll();

        $this->buildStorage->write(
            'heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'numberOfRoutes' => count($routes),
                'routes' => Json::encode($routes),
                'sportTypes' => $importedSportTypes->filter(
                    fn (SportType $sportType): bool => $sportType->supportsReverseGeocoding()
                ),
                'numberOfCountriesWithWorkouts' => count(array_filter(array_unique($routes->map(
                    fn (Route $route): ?string => $route->getLocation()->getCountryCode()
                )))),
                'heatmapConfig' => $this->heatmapConfig,
            ]),
        );
    }
}
