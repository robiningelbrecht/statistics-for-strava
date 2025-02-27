<?php

declare(strict_types=1);

namespace App\Domain\App\BuildSegmentsHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
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
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildSegmentsHtml);

        $activities = $this->activitiesEnricher->getEnrichedActivities();
        $importedSportTypes = $this->sportTypeRepository->findAll();

        $dataDatableRows = [];
        $pagination = Pagination::fromOffsetAndLimit(0, 100);

        do {
            $segments = $this->segmentRepository->findAll($pagination);
            /** @var Segment $segment */
            foreach ($segments as $segment) {
                $segmentEfforts = $this->segmentEffortRepository->findBySegmentId($segment->getId(), 10);
                $segment->enrichWithNumberOfTimesRidden($this->segmentEffortRepository->countBySegmentId($segment->getId()));
                $segment->enrichWithBestEffort($segmentEfforts->getBestEffort());

                /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $segmentEffort */
                foreach ($segmentEfforts as $segmentEffort) {
                    $activity = $activities->getByActivityId($segmentEffort->getActivityId());
                    $segmentEffort->enrichWithActivity($activity);
                }

                $this->filesystem->write(
                    'build/html/segment/'.$segment->getId().'.html',
                    $this->twig->load('html/segment/segment.html.twig')->render([
                        'segment' => $segment,
                        'segmentEfforts' => $segmentEfforts->slice(0, 10),
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

        $this->filesystem->write(
            'build/html/fetch-json/segment-data-table.json',
            Json::encode($dataDatableRows),
        );

        $this->filesystem->write(
            'build/html/segments.html',
            $this->twig->load('html/segment/segments.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'totalSegmentCount' => $this->segmentRepository->count(),
            ]),
        );
    }
}
