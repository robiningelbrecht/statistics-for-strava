<?php

declare(strict_types=1);

namespace App\Application\Build\BuildActivityMetrics;

use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamProfileCharts;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BuildActivityMetricsCommandHandler implements CommandHandler
{
    public function __construct(
        private EnrichedActivities $enrichedActivities,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private UnitSystem $unitSystem,
        private FilesystemOperator $apiStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildActivityMetrics);

        $activities = $this->enrichedActivities->findAll();

        foreach ($activities as $activity) {
            try {
                $combinedActivityStream = $this->combinedActivityStreamRepository->findOneForActivityAndUnitSystem(
                    activityId: $activity->getId(),
                    unitSystem: $this->unitSystem
                );
            } catch (EntityNotFound) {
                continue;
            }

            $maximumNumberOfDigits = $combinedActivityStream->getMaximumNumberOfDigits();
            $distances = $combinedActivityStream->getDistances();
            $times = $combinedActivityStream->getTimes();
            $grades = $combinedActivityStream->getGrades();
            $coordinateMap = $combinedActivityStream->getCoordinates();

            $streamTypesForCharts = $combinedActivityStream->getStreamTypesForCharts();
            $items = [];
            foreach ($streamTypesForCharts as $combinedStreamType) {
                $items[] = [
                    'yAxisData' => $combinedActivityStream->getChartStreamData($combinedStreamType),
                    'yAxisStreamType' => $combinedStreamType,
                ];
            }

            $combinedCharts = CombinedStreamProfileCharts::create(
                items: array_reverse($items),
                topXAxisData: $times,
                bottomXAxisData: $distances,
                bottomXAxisSuffix: $this->unitSystem->distanceSymbol(),
                grades: $grades,
                maximumNumberOfDigitsOnYAxis: $maximumNumberOfDigits,
                unitSystem: $this->unitSystem,
                translator: $this->translator,
            );
            $profileChart = $combinedCharts->build();

            $unprefixedActivityId = $activity->getId()->toUnprefixedString();
            $this->apiStorage->write(
                sprintf('activity/%s/metrics.json', $unprefixedActivityId),
                (string) Json::encodeAndCompress($profileChart),
            );
            $this->apiStorage->write(
                sprintf('activity/%s/coordinates.json', $unprefixedActivityId),
                (string) Json::encodeAndCompress($coordinateMap),
            );
        }
    }
}
