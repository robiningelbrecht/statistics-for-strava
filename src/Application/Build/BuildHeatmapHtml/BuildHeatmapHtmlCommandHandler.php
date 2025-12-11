<?php

declare(strict_types=1);

namespace App\Application\Build\BuildHeatmapHtml;

use App\Domain\Activity\Route\Route;
use App\Domain\Activity\Route\RouteRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildHeatmapHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private RouteRepository $routeRepository,
        private SportTypeRepository $sportTypeRepository,
        private HeatmapConfig $heatmapConfig,
        private Environment $twig,
        private UnitSystem $unitSystem,
        private DateAndTimeFormat $dateAndTimeFormat,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildHeatmapHtml);

        $importedSportTypes = $this->sportTypeRepository->findAll();
        $routes = $this->routeRepository->findAll();

        foreach ($routes as $route) {
            $route->enrichWithUnitSystemAndDateTimeFormat(
                unitSystem: $this->unitSystem,
                dateAndTimeFormat: $this->dateAndTimeFormat,
            );
        }

        $this->buildStorage->write(
            'heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'numberOfRoutes' => count($routes),
                'routes' => Json::encode($routes),
                'sportTypes' => $importedSportTypes->filter(
                    fn (SportType $sportType): bool => $sportType->supportsReverseGeocoding()
                ),
                'numberOfCountriesWithWorkouts' => count(array_filter(array_unique($routes->map(
                    fn (Route $route): ?string => $route->getRouteGeography()->getStartingPointCountryCode()
                )))),
                'heatmapConfig' => $this->heatmapConfig,
            ]),
        );
    }
}
