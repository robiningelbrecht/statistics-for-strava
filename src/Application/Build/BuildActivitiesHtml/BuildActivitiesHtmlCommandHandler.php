<?php

declare(strict_types=1);

namespace App\Application\Build\BuildActivitiesHtml;

use App\Application\Countries;
use App\Domain\Activity\ActivityTotals;
use App\Domain\Activity\BestEffort\BestEffortsCalculator;
use App\Domain\Activity\Device\DeviceRepository;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\HeartRateDistributionChart;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\LeafletMap;
use App\Domain\Activity\PowerDistributionChart;
use App\Domain\Activity\Split\ActivitySplitRepository;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedStreamProfileChart;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Activity\VelocityDistributionChart;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Domain\Ftp\FtpHistory;
use App\Domain\Gear\GearRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Theme\Theme;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildActivitiesHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private EnrichedActivities $enrichedActivities,
        private ActivityStreamRepository $activityStreamRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityLapRepository $activityLapRepository,
        private SportTypeRepository $sportTypeRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private GearRepository $gearRepository,
        private DeviceRepository $deviceRepository,
        private FtpHistory $ftpHistory,
        private BestEffortsCalculator $bestEffortsCalculator,
        private HeartRateZoneConfiguration $heartRateZoneConfiguration,
        private Countries $countries,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildActivitiesHtml);

        $now = $command->getCurrentDateTime();
        $athlete = $this->athleteRepository->find();
        $importedSportTypes = $this->sportTypeRepository->findAll();

        $activities = $this->enrichedActivities->findAll();

        $activityTotals = ActivityTotals::getInstance(
            activities: $activities,
            now: $now,
            translator: $this->translator,
        );

        $this->buildStorage->write(
            'activities.html',
            $this->twig->load('html/activity/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'devices' => $this->deviceRepository->findAll(),
                'activityTotals' => $activityTotals,
                'countries' => $this->countries->getUsedInActivities(),
                'gears' => $this->gearRepository->findAllUsed(),
            ]),
        );

        $dataDatableRows = [];
        foreach ($activities as $activity) {
            $activityType = $activity->getSportType()->getActivityType();

            $heartRateStream = null;
            $powerStream = null;
            $velocityStream = null;
            try {
                $heartRateStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::HEART_RATE);
            } catch (EntityNotFound) {
            }
            try {
                $powerStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::WATTS);
            } catch (EntityNotFound) {
            }
            try {
                $velocityStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::VELOCITY);
            } catch (EntityNotFound) {
            }

            $distributionCharts = [];
            if ($activity->getAverageHeartRate() && $heartRateStream && [] !== $heartRateStream->getValueDistribution()) {
                $distributionCharts[] = [
                    'title' => $this->translator->trans('Heart rate distribution'),
                    'data' => Json::encode(HeartRateDistributionChart::create(
                        heartRateData: $heartRateStream->getValueDistribution(),
                        averageHeartRate: $activity->getAverageHeartRate(),
                        athleteMaxHeartRate: $athlete->getMaxHeartRate($activity->getStartDate()),
                        heartRateZones: $this->heartRateZoneConfiguration->getHeartRateZonesFor(
                            sportType: $activity->getSportType(),
                            on: $activity->getStartDate()
                        )
                    )->build()),
                ];
            }

            if ($activityType->supportsPowerData() && $activity->getAveragePower()
                && $powerStream && count($powerStream->getValueDistribution()) > 1) {
                $ftp = null;
                try {
                    $ftp = $this->ftpHistory->find(
                        activityType: $activityType,
                        on: $activity->getStartDate()
                    );
                } catch (EntityNotFound) {
                }

                $powerDistributionChart = PowerDistributionChart::create(
                    powerData: $powerStream->getValueDistribution(),
                    averagePower: $activity->getAveragePower(),
                    ftp: $ftp,
                )->build();

                if (!is_null($powerDistributionChart)) {
                    $distributionCharts[] = [
                        'title' => $this->translator->trans('Power distribution'),
                        'data' => Json::encode($powerDistributionChart),
                    ];
                }
            }
            if ($velocityStream && [] !== $velocityStream->getValueDistribution()) {
                $velocityUnitPreference = $activity->getSportType()->getVelocityDisplayPreference();

                $velocityDistributionChart = VelocityDistributionChart::create(
                    velocityData: $velocityStream->getValueDistribution(),
                    averageSpeed: $activity->getAverageSpeed(),
                    sportType: $activity->getSportType(),
                    unitSystem: $this->unitSystem,
                )->build();

                if (!is_null($velocityDistributionChart)) {
                    $distributionCharts[] = [
                        'title' => match (true) {
                            $velocityUnitPreference instanceof KmPerHour => $this->translator->trans('Speed distribution'),
                            default => $this->translator->trans('Pace distribution'),
                        },
                        'data' => Json::encode($velocityDistributionChart),
                    ];
                }
            }

            $activitySplits = $this->activitySplitRepository->findBy(
                activityId: $activity->getId(),
                unitSystem: $this->unitSystem
            );

            if (!$activitySplits->isEmpty() && $heartRateStream) {
                /** @var \App\Domain\Activity\Split\ActivitySplit $activitySplit */
                $sumSplitMovingTimeInSeconds = 0;
                foreach ($activitySplits as $activitySplit) {
                    $movingTimeInSeconds = $activitySplit->getMovingTimeInSeconds();
                    // Enrich ActivitySplit with average heart rate.
                    $heartRatesForCurrentSplit = array_slice(
                        array: $heartRateStream->getData(),
                        offset: $sumSplitMovingTimeInSeconds,
                        length: $movingTimeInSeconds
                    );
                    if (0 === count($heartRatesForCurrentSplit)) {
                        continue; // @codeCoverageIgnore
                    }
                    $averageHeartRate = (int) round(array_sum($heartRatesForCurrentSplit) / count($heartRatesForCurrentSplit));

                    $activitySplit->enrichWithAverageHeartRate($averageHeartRate);
                    $sumSplitMovingTimeInSeconds += $movingTimeInSeconds;
                }
            }

            $activityProfileCharts = [];
            $coordinateMap = [];
            if ($activityType->supportsCombinedStreamCalculation()) {
                try {
                    $combinedActivityStream = $this->combinedActivityStreamRepository->findOneForActivityAndUnitSystem(
                        activityId: $activity->getId(),
                        unitSystem: $this->unitSystem
                    );

                    $maximumNumberOfDigits = $combinedActivityStream->getMaximumNumberOfDigits();
                    $distances = $combinedActivityStream->getDistances();
                    $times = $combinedActivityStream->getTimes();
                    $coordinateMap = $combinedActivityStream->getCoordinates();

                    $streamTypesForCharts = $combinedActivityStream->getStreamTypesForCharts();
                    foreach ($streamTypesForCharts as $index => $combinedStreamType) {
                        $xAxisPosition = match (true) {
                            0 === $index => Theme::POSITION_BOTTOM,
                            $index === count($streamTypesForCharts) - 1 => Theme::POSITION_TOP,
                            default => null,
                        };
                        $xAxisData = match (true) {
                            Theme::POSITION_BOTTOM === $xAxisPosition && [] !== $distances => $distances,
                            default => $times,
                        };
                        $xAxisLabelSuffix = match (true) {
                            Theme::POSITION_BOTTOM === $xAxisPosition && [] !== $distances => $this->unitSystem->distanceSymbol(),
                            default => null,
                        };

                        $chart = CombinedStreamProfileChart::create(
                            xAxisData: $xAxisData,
                            xAxisPosition: $xAxisPosition,
                            xAxisLabelSuffix: $xAxisLabelSuffix,
                            yAxisData: $combinedActivityStream->getChartStreamData($combinedStreamType),
                            maximumNumberOfDigitsOnYAxis: $maximumNumberOfDigits,
                            yAxisStreamType: $combinedStreamType,
                            unitSystem: $this->unitSystem,
                            translator: $this->translator
                        );
                        $activityProfileCharts[$combinedStreamType->value] = Json::encode($chart->build());
                    }
                } catch (EntityNotFound) {
                }
            }

            $leafletMap = $activity->getLeafletMap();
            $templateName = sprintf('html/activity/%s.html.twig', $activity->getSportType()->getTemplateName());
            $gpxFileLocation = sprintf('activities/gpx/%s.gpx', $activity->getId());
            $activityHasTimeStream = $this->activityStreamRepository->hasOneForActivityAndStreamType($activity->getId(), StreamType::TIME);

            $this->buildStorage->write(
                'activity/'.$activity->getId().'.html',
                $this->twig->load($templateName)->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap instanceof LeafletMap ? [
                        'routes' => [$activity->getPolyline()],
                        'map' => $leafletMap,
                        'gpxLink' => $activityHasTimeStream ? 'files/'.$gpxFileLocation : null,
                    ] : null,
                    'distributionCharts' => $distributionCharts,
                    'segmentEfforts' => $this->segmentEffortRepository->findByActivityId($activity->getId()),
                    'splits' => $activitySplits,
                    'laps' => $this->activityLapRepository->findBy($activity->getId()),
                    'profileCharts' => array_reverse($activityProfileCharts),
                    'bestEfforts' => $this->bestEffortsCalculator->forActivity($activity->getId()),
                    'coordinateMap' => Json::encode($coordinateMap),
                ]),
            );

            $dataDatableRows[] = DataTableRow::create(
                markup: $this->twig->load('html/activity/activity-data-table-row.html.twig')->render([
                    'timeIntervals' => ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED,
                    'activity' => $activity,
                ]),
                searchables: $activity->getSearchables(),
                filterables: $activity->getFilterables(),
                sortValues: $activity->getSortables(),
                summables: $activity->getSummables($this->unitSystem),
            );
        }

        $this->buildStorage->write(
            'fetch-json/activity-data-table.json',
            Json::encode($dataDatableRows),
        );
    }
}
