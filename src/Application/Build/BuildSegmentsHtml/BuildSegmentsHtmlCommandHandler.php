<?php

declare(strict_types=1);

namespace App\Application\Build\BuildSegmentsHtml;

use App\Application\Countries;
use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Segment\Segment;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\DataTableRow;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildSegmentsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private Countries $countries,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildSegmentsHtml);

        $importedSportTypes = $this->sportTypeRepository->findAll();

        $dataDatableRows = [];
        $pagination = Pagination::fromOffsetAndLimit(0, 100);

        do {
            $segments = $this->segmentRepository->findAll($pagination);
            /** @var Segment $segment */
            foreach ($segments as $segment) {
                $segmentEffortsTopTen = $this->segmentEffortRepository->findTopXBySegmentId($segment->getId(), 10);
                $segmentEffortsHistory = $this->segmentEffortRepository->findHistoryBySegmentId($segment->getId());
                $segment->enrichWithNumberOfTimesRidden($this->segmentEffortRepository->countBySegmentId($segment->getId()));
                $segment->enrichWithBestEffort($segmentEffortsTopTen->getBestEffort());

                /** @var \App\Domain\Segment\SegmentEffort\SegmentEffort $segmentEffort */
                foreach ($segmentEffortsTopTen as $segmentEffort) {
                    $activity = $this->activitiesEnricher->getEnrichedActivity($segmentEffort->getActivityId());
                    $segmentEffort->enrichWithActivity($activity);
                }

                /** @var \App\Domain\Segment\SegmentEffort\SegmentEffort $segmentEffort */
                foreach ($segmentEffortsHistory as $segmentEffort) {
                    $activity = $this->activitiesEnricher->getEnrichedActivity($segmentEffort->getActivityId());
                    $segmentEffort->enrichWithActivity($activity);
                }
                if ($lastEffortDate = $segmentEffortsHistory->getFirst()?->getStartDateTime()) {
                    $segment->enrichWithLastEffortDate($lastEffortDate);
                }

                $leafletMap = $segment->getLeafletMap();
                $this->buildStorage->write(
                    'segment/'.$segment->getId().'.html',
                    $this->twig->load('html/segment/segment.html.twig')->render([
                        'segment' => $segment,
                        'segmentEffortsTopTen' => $segmentEffortsTopTen,
                        'segmentEffortsHistory' => $segmentEffortsHistory,
                        'leaflet' => $leafletMap ? [
                            'routes' => [$segment->getPolyline()],
                            'map' => $leafletMap,
                        ] : null,
                    ]),
                );

                $dataDatableRows[] = DataTableRow::create(
                    markup: $this->twig->load('html/segment/segment-data-table-row.html.twig')->render([
                        'segment' => $segment,
                    ]),
                    searchables: $segment->getSearchables(),
                    filterables: $segment->getFilterables(),
                    sortValues: $segment->getSortables(),
                    summables: []
                );
            }

            $pagination = $pagination->next();
        } while (!$segments->isEmpty());

        $this->buildStorage->write(
            'fetch-json/segment-data-table.json',
            Json::encode($dataDatableRows),
        );

        $this->buildStorage->write(
            'segments.html',
            $this->twig->load('html/segment/segments.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'countries' => $this->countries->getUsedInSegments(),
                'totalSegmentCount' => $this->segmentRepository->count(),
            ]),
        );
    }
}
