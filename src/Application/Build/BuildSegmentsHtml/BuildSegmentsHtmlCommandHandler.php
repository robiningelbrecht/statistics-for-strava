<?php

declare(strict_types=1);

namespace App\Application\Build\BuildSegmentsHtml;

use App\Application\Countries;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Segment\Segment;
use App\Domain\Segment\SegmentEffort\SegmentEffortHistoryChart;
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
        private Countries $countries,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private FilesystemOperator $apiStorage,
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
                $segment = $segment
                    ->withNumberOfTimesRidden($this->segmentEffortRepository->countBySegmentId($segment->getId()))
                    ->withBestEffort($segmentEffortsTopTen->getBestEffort())
                    ->withLastEffortDate($segmentEffortsHistory->getFirst()?->getStartDateTime());

                $leafletMap = $segment->getLeafletMap();
                $this->buildStorage->write(
                    'segment/'.$segment->getId().'.html',
                    $this->twig->load('html/segment/segment.html.twig')->render([
                        'segment' => $segment,
                        'segmentEffortsTopTen' => $segmentEffortsTopTen,
                        'segmentEffortsHistoryChart' => Json::encode(
                            SegmentEffortHistoryChart::create($segmentEffortsHistory)->build()
                        ),
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

        $this->apiStorage->write(
            'segment/data-table.json',
            (string) Json::encodeAndCompress($dataDatableRows),
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
