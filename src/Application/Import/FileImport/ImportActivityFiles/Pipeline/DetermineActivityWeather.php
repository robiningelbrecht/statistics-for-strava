<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\ImportActivityFiles\Pipeline;

use App\Domain\Integration\Weather\OpenMeteo\OpenMeteo;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoArchiveApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoForecastApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\Weather;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 80)]
final readonly class DetermineActivityWeather implements ImportActivityFileStep
{
    public function __construct(
        private OpenMeteo $openMeteo,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');

        if (!$activity->getSportType()->supportsWeather()) {
            return $context;
        }
        if (!$activity->getStartingCoordinate() instanceof Coordinate) {
            return $context;
        }

        try {
            $weather = Weather::fromRawData(
                $this->openMeteo->getWeatherStats(
                    coordinate: $activity->getStartingCoordinate(),
                    date: $activity->getStartDate()
                ),
                on: $activity->getStartDate()
            );

            return $context->withActivity($activity->withWeather($weather));
        } catch (OpenMeteoForecastApiCallHasFailed|OpenMeteoArchiveApiCallHasFailed) {
        }

        return $context;
    }
}
