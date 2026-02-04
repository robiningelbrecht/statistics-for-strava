<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Integration\Weather\OpenMeteo\OpenMeteo;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoArchiveApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\OpenMeteoForecastApiCallHasFailed;
use App\Domain\Integration\Weather\OpenMeteo\Weather;

final readonly class DetermineActivityWeather implements ActivityImportStep
{
    public function __construct(
        private OpenMeteo $openMeteo,
    ) {
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');

        if (!$context->isNewActivity()) {
            return $context;
        }
        if (!$activity->getSportType()->supportsWeather()) {
            return $context;
        }
        if (!$activity->getStartingCoordinate() instanceof \App\Infrastructure\ValueObject\Geography\Coordinate) {
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
