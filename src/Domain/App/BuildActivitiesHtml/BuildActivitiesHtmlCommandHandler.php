<?php

declare(strict_types=1);

namespace App\Domain\App\BuildActivitiesHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\HeartRateDistributionChart;
use App\Domain\Strava\Activity\Lap\ActivityLapRepository;
use App\Domain\Strava\Activity\PowerDistributionChart;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedStreamProfileChart;
use App\Domain\Strava\Activity\Stream\CombinedStream\CombinedStreamType;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildActivitiesHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityLapRepository $activityLapRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private SportTypeRepository $sportTypeRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private GearRepository $gearRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private HeartRateZoneConfiguration $heartRateZoneConfiguration,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildActivitiesHtml);

        $now = $command->getCurrentDateTime();
        $athlete = $this->athleteRepository->find();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $bestEfforts = $this->activityBestEffortRepository->findAll();

        $activities = $this->activitiesEnricher->getEnrichedActivities();

        $activityTotals = ActivityTotals::getInstance(
            activities: $activities,
            now: $now,
            translator: $this->translator
        );

        $countriesWithWorkouts = [];
        foreach ($activities as $activity) {
            if (!$countryCode = $activity->getLocation()?->getCountryCode()) {
                continue;
            }
            if (isset($countriesWithWorkouts[$countryCode])) {
                continue;
            }

            $countriesWithWorkouts[$countryCode] = Countries::getName(
                country: strtoupper($countryCode),
                displayLocale: $this->localeSwitcher->getLocale()
            );
        }

        $this->buildStorage->write(
            'activities.html',
            $this->twig->load('html/activity/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'activityTotals' => $activityTotals,
                'countries' => $countriesWithWorkouts,
                'gears' => $this->gearRepository->findAll(),
            ]),
        );

        $dataDatableRows = [];
        foreach ($activities as $activity) {
            $activityType = $activity->getSportType()->getActivityType();
            $heartRateDistributionChart = null;
            if ($activity->getAverageHeartRate()
                && ($timeInSecondsPerHeartRate = $this->activityHeartRateRepository->findTimeInSecondsPerHeartRateForActivity($activity->getId()))) {
                $heartRateDistributionChart = HeartRateDistributionChart::create(
                    heartRateData: $timeInSecondsPerHeartRate,
                    averageHeartRate: $activity->getAverageHeartRate(),
                    athleteMaxHeartRate: $athlete->getMaxHeartRate($activity->getStartDate()),
                    heartRateZones: $this->heartRateZoneConfiguration->getHeartRateZonesFor(
                        sportType: $activity->getSportType(),
                        on: $activity->getStartDate()
                    )
                );
            }

            $heartRateStream = null;
            try {
                $heartRateStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::HEART_RATE);
            } catch (EntityNotFound) {
            }

            $timeInSecondsPerWattage = null;
            $powerDistributionChart = null;
            if ($activityType->supportsPowerDistributionChart() && $activity->getAveragePower()
                && ($timeInSecondsPerWattage = $this->activityPowerRepository->findTimeInSecondsPerWattageForActivity($activity->getId()))) {
                $powerDistributionChart = PowerDistributionChart::create(
                    powerData: $timeInSecondsPerWattage,
                    averagePower: $activity->getAveragePower(),
                );
            }

            $activitySplits = $this->activitySplitRepository->findBy(
                activityId: $activity->getId(),
                unitSystem: $this->unitSystem
            );

            if (!$activitySplits->isEmpty() && $heartRateStream) {
                /** @var \App\Domain\Strava\Activity\Split\ActivitySplit $activitySplit */
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
                        continue;
                    }
                    $averageHeartRate = (int) round(array_sum($heartRatesForCurrentSplit) / count($heartRatesForCurrentSplit));

                    $activitySplit->enrichWithAverageHeartRate($averageHeartRate);
                    $sumSplitMovingTimeInSeconds += $movingTimeInSeconds;
                }
            }

            $activityProfileCharts = [];
            if ($activityType->supportsCombinedStreamCalculation()) {
                try {
                    $combinedActivityStream = $this->combinedActivityStreamRepository->findOneForActivityAndUnitSystem(
                        activityId: $activity->getId(),
                        unitSystem: $this->unitSystem
                    );

                    $distances = $combinedActivityStream->getDistances();

                    $combinedStreamTypes = $combinedActivityStream->getStreamTypes();
                    /** @var CombinedStreamType $combinedStreamType */
                    $firstIteration = true;
                    foreach ($combinedStreamTypes as $combinedStreamType) {
                        if (CombinedStreamType::DISTANCE === $combinedStreamType) {
                            continue;
                        }

                        if (!$data = $combinedActivityStream->getOtherStreamData($combinedStreamType)) {
                            continue;
                        }

                        $chart = CombinedStreamProfileChart::create(
                            distances: $distances,
                            yAxisData: $data,
                            yAxisStreamType: $combinedStreamType,
                            unitSystem: $this->unitSystem,
                            showXAxis: $firstIteration,
                            translator: $this->translator
                        );
                        $activityProfileCharts[$combinedStreamType->value] = Json::encode($chart->build());
                        $firstIteration = false;
                    }
                } catch (EntityNotFound) {
                }
            }

            $leafletMap = $activity->getLeafletMap();
            $this->buildStorage->write(
                'activity/'.$activity->getId().'.html',
                $this->twig->load('html/activity/activity.html.twig')->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap ? [
                        'routes' => [$activity->getPolyline()],
                        'map' => $leafletMap,
                    ] : null,
                    'heartRateDistributionChart' => $heartRateDistributionChart ? Json::encode($heartRateDistributionChart->build()) : null,
                    'powerDistributionChart' => $powerDistributionChart ? Json::encode($powerDistributionChart->build()) : null,
                    'segmentEfforts' => $this->segmentEffortRepository->findByActivityId($activity->getId()),
                    'splits' => $activitySplits,
                    'laps' => $this->activityLapRepository->findBy($activity->getId()),
                    'profileCharts' => array_reverse($activityProfileCharts),
                    'bestEfforts' => $bestEfforts->getByActivity($activity->getId()),
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
