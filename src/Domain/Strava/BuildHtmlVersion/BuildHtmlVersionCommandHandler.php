<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\Activity\ActivityHeatmapChartBuilder;
use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsChartsBuilder;
use App\Domain\Strava\Activity\DistanceBreakdown;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonChartBuilder;
use App\Domain\Strava\Activity\Eddington\EddingtonHistoryChartBuilder;
use App\Domain\Strava\Activity\HeartRateChartBuilder;
use App\Domain\Strava\Activity\HeartRateDistributionChartBuilder;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\PowerDistributionChartBuilder;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\PowerOutputChartBuilder;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStats;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStatsChartsBuilder;
use App\Domain\Strava\Activity\WeeklyDistanceChartBuilder;
use App\Domain\Strava\Activity\YearlyDistance\YearlyDistanceChartBuilder;
use App\Domain\Strava\Activity\YearlyDistance\YearlyStatistics;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChartBuilder;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\Consistency\ChallengeConsistency;
use App\Domain\Strava\Ftp\FtpHistoryChartBuilder;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChartBuilder;
use App\Domain\Strava\Gear\DistancePerMonthPerGearChartBuilder;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\MonthlyStatistics;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Localisation\LocaleSwitcher;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\Years;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    private const string APP_VERSION = 'v0.4.6';

    public function __construct(
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private GearRepository $gearRepository,
        private ImageRepository $imageRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private AthleteRepository $athleteRepository,
        private AthleteWeightRepository $athleteWeightRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private FtpRepository $ftpRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private ActivityIntensity $activityIntensity,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildHtmlVersion);

        $this->localeSwitcher->setLocale();
        $now = $this->clock->getCurrentDateTimeImmutable();

        $athlete = $this->athleteRepository->find();
        $allActivities = $this->activityRepository->findAll();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $importedActivityTypes = $this->activityTypeRepository->findAll();
        $activitiesPerActivityType = [];
        foreach ($importedActivityTypes as $activityType) {
            $activitiesPerActivityType[$activityType->value] = $allActivities->filterOnActivityType($activityType);
        }

        $allChallenges = $this->challengeRepository->findAll();
        $allGear = $this->gearRepository->findAll();
        $allImages = $this->imageRepository->findAll();
        $allFtps = $this->ftpRepository->findAll();

        $command->getOutput()->writeln('  => Calculating Eddington');
        $eddingtonPerActivityType = [];
        /** @var \App\Domain\Strava\Activity\ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if (!$activityType->supportsEddington()) {
                continue;
            }
            if ($activitiesPerActivityType[$activityType->value]->isEmpty()) {
                continue;
            }
            $eddington = Eddington::fromActivities(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            $eddingtonPerActivityType[$activityType->value] = $eddington;
        }

        $command->getOutput()->writeln('  => Calculating weekday stats');
        $weekdayStats = WeekdayStats::create(
            activities: $allActivities,
            translator: $this->translator
        );

        $command->getOutput()->writeln('  => Calculating daytime stats');
        $dayTimeStats = DaytimeStats::create($allActivities);

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $command->getOutput()->writeln('  => Calculating monthly stats');
        $monthlyStatistics = MonthlyStatistics::create(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );
        $command->getOutput()->writeln('  => Calculating best power outputs');
        $bestPowerOutputs = $this->activityPowerRepository->findBest();

        $command->getOutput()->writeln('  => Enriching activities with data');
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($allActivities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );

            try {
                $cadenceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::CADENCE
                );

                if (!empty($cadenceStream->getData())) {
                    $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
                }
            } catch (EntityNotFound) {
            }
        }

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightRepository->find($ftp->getSetOn())->getWeightInKg()
                );
            } catch (EntityNotFound) {
            }
        }

        $command->getOutput()->writeln('  => Building index.html');
        $this->filesystem->write(
            'build/html/index.html',
            $this->twig->load('html/index.html.twig')->render([
                'totalActivityCount' => count($allActivities),
                'eddingtons' => $eddingtonPerActivityType,
                'completedChallenges' => count($allChallenges),
                'totalPhotoCount' => count($allImages),
                'lastUpdate' => $now,
                'athlete' => $athlete,
                'currentAppVersion' => self::APP_VERSION,
            ]),
        );

        $command->getOutput()->writeln('  => Building dashboard.html');

        $weeklyDistanceCharts = [];
        $distanceBreakdowns = [];
        $yearlyDistanceCharts = [];
        $yearlyStatistics = [];
        /** @var \App\Domain\Strava\Activity\ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if ($activitiesPerActivityType[$activityType->value]->isEmpty()) {
                continue;
            }

            if ($activityType->supportsWeeklyDistanceStats() && $chartData = WeeklyDistanceChartBuilder::create(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem,
                translator: $this->translator,
                now: $now,
            )->build()) {
                $weeklyDistanceCharts[$activityType->value] = Json::encode($chartData);
            }

            if ($activityType->supportsDistanceBreakdownStats()) {
                $distanceBreakdown = DistanceBreakdown::create(
                    activities: $activitiesPerActivityType[$activityType->value],
                    unitSystem: $this->unitSystem
                );

                if ($build = $distanceBreakdown->build()) {
                    $distanceBreakdowns[$activityType->value] = $build;
                }
            }

            if ($activityType->supportsYearlyStats()) {
                $yearlyDistanceCharts[$activityType->value] = Json::encode(
                    YearlyDistanceChartBuilder::create(
                        activities: $activitiesPerActivityType[$activityType->value],
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now
                    )->build()
                );

                $yearlyStatistics[$activityType->value] = YearlyStatistics::create(
                    activities: $activitiesPerActivityType[$activityType->value],
                    years: $allYears
                );
            }
        }

        $this->filesystem->write(
            'build/html/dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'intro' => ActivityTotals::create(
                    activities: $allActivities,
                    now: $now,
                ),
                'weeklyDistanceCharts' => $weeklyDistanceCharts,
                'powerOutputs' => $bestPowerOutputs,
                'activityHeatmapChart' => Json::encode(
                    ActivityHeatmapChartBuilder::create(
                        activities: $allActivities,
                        activityIntensity: $this->activityIntensity,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
                'weekdayStatsChart' => Json::encode(
                    WeekdayStatsChartsBuilder::create($weekdayStats)->build(),
                ),
                'weekdayStats' => $weekdayStats,
                'daytimeStatsChart' => Json::encode(
                    DaytimeStatsChartsBuilder::create(
                        daytimeStats: $dayTimeStats,
                        translator: $this->translator,
                    )->build(),
                ),
                'daytimeStats' => $dayTimeStats,
                'distanceBreakdowns' => $distanceBreakdowns,
                'trivia' => Trivia::create($allActivities),
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChartBuilder::create(
                        ftps: $allFtps,
                        now: $now
                    )->build()
                ) : null,
                'timeInHeartRateZoneChart' => Json::encode(
                    TimeInHeartRateZoneChartBuilder::create(
                        timeInSecondsInHeartRateZoneOne: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::ONE),
                        timeInSecondsInHeartRateZoneTwo: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::TWO),
                        timeInSecondsInHeartRateZoneThree: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::THREE),
                        timeInSecondsInHeartRateZoneFour: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FOUR),
                        timeInSecondsInHeartRateZoneFive: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FIVE),
                        translator: $this->translator,
                    )->build(),
                ),
                'challengeConsistency' => ChallengeConsistency::create(
                    months: $allMonths,
                    activities: $allActivities
                ),
                'yearlyDistanceCharts' => $yearlyDistanceCharts,
                'yearlyStatistics' => $yearlyStatistics,
            ]),
        );

        if (!empty($bestPowerOutputs)) {
            $command->getOutput()->writeln('  => Building power-output.html');
            $this->filesystem->write(
                'build/html/power-output.html',
                $this->twig->load('html/power-output.html.twig')->render([
                    'powerOutputChart' => Json::encode(
                        PowerOutputChartBuilder::create($bestPowerOutputs)->build()
                    ),
                ]),
            );
        }

        $command->getOutput()->writeln('  => Building photos.html');
        $this->filesystem->write(
            'build/html/photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'images' => $allImages,
                'sportTypes' => $importedSportTypes,
            ]),
        );

        $command->getOutput()->writeln('  => Building challenges.html');
        $challengesGroupedByMonth = [];
        foreach ($allChallenges as $challenge) {
            $challengesGroupedByMonth[$challenge->getCreatedOn()->translatedFormat('F Y')][] = $challenge;
        }
        $this->filesystem->write(
            'build/html/challenges.html',
            $this->twig->load('html/challenges.html.twig')->render([
                'challengesGroupedPerMonth' => $challengesGroupedByMonth,
            ]),
        );

        $command->getOutput()->writeln('  => Building eddington.html');

        $eddingtonChartsPerActivityType = [];
        $eddingtonHistoryChartsPerActivityType = [];
        foreach ($eddingtonPerActivityType as $activityType => $eddington) {
            $eddingtonChartsPerActivityType[$activityType] = Json::encode(
                EddingtonChartBuilder::create(
                    eddington: $eddington,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
            $eddingtonHistoryChartsPerActivityType[$activityType] = Json::encode(
                EddingtonHistoryChartBuilder::create(
                    eddington: $eddington,
                )->build()
            );
        }

        $this->filesystem->write(
            'build/html/eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'activityTypes' => $importedActivityTypes,
                'eddingtons' => $eddingtonPerActivityType,
                'eddingtonCharts' => $eddingtonChartsPerActivityType,
                'eddingtonHistoryCharts' => $eddingtonHistoryChartsPerActivityType,
                'distanceUnit' => Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol(),
            ]),
        );

        $command->getOutput()->writeln('  => Building segments.html');
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
                    $activity = $allActivities->getByActivityId($segmentEffort->getActivityId());
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
                    sortValues: $segment->getSortables()
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
            ]),
        );

        $command->getOutput()->writeln('  => Building monthly-stats.html');
        $this->filesystem->write(
            'build/html/monthly-stats.html',
            $this->twig->load('html/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStatistics,
                'sportTypes' => $importedSportTypes,
            ]),
        );

        /** @var Month $month */
        foreach ($allMonths as $month) {
            $this->filesystem->write(
                'build/html/month/month-'.$month->getId().'.html',
                $this->twig->load('html/month.html.twig')->render([
                    'hasPreviousMonth' => $month->getId() != $allActivities->getFirstActivityStartDate()->format(Month::MONTH_ID_FORMAT),
                    'hasNextMonth' => $month->getId() != $now->format(Month::MONTH_ID_FORMAT),
                    'statistics' => $monthlyStatistics->getStatisticsForMonth($month),
                    'calendar' => Calendar::create(
                        month: $month,
                        activities: $allActivities
                    ),
                ]),
            );
        }

        $command->getOutput()->writeln('  => Building gear-stats.html');
        $this->filesystem->write(
            'build/html/gear-stats.html',
            $this->twig->load('html/gear-stats.html.twig')->render([
                'gearStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allGear
                ),
                'distancePerMonthPerGearChart' => Json::encode(
                    DistancePerMonthPerGearChartBuilder::create(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        months: $allMonths,
                    )->build()
                ),
                'distanceOverTimePerGear' => Json::encode(
                    DistanceOverTimePerGearChartBuilder::create(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
            ]),
        );

        $routesPerCountry = [];
        $routesInMostActiveState = [];
        $mostActiveState = $this->activityRepository->findMostActiveState();
        foreach ($allActivities as $activity) {
            if (!$activity->getSportType()->supportsReverseGeocoding()) {
                continue;
            }
            if (!$polyline = $activity->getPolyline()) {
                continue;
            }
            if (!$countryCode = $activity->getLocation()?->getCountryCode()) {
                continue;
            }
            $routesPerCountry[$countryCode][] = $polyline;
            if ($activity->getLocation()?->getState() === $mostActiveState) {
                $routesInMostActiveState[] = $polyline;
            }
        }

        $command->getOutput()->writeln('  => Building heatmap.html');
        $this->filesystem->write(
            'build/html/heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'routesPerCountry' => Json::encode($routesPerCountry),
                'routesInMostActiveState' => Json::encode($routesInMostActiveState),
            ]),
        );

        $command->getOutput()->writeln('  => Building activities.html');
        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activity/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
            ]),
        );

        $dataDatableRows = [];
        foreach ($allActivities as $activity) {
            $timeInSecondsPerHeartRate = $this->activityHeartRateRepository->findTimeInSecondsPerHeartRateForActivity($activity->getId());
            $heartRateStream = null;
            if ($activity->getSportType()->getActivityType()->supportsHeartRateOverTimeChart()) {
                try {
                    $heartRateStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::HEART_RATE);
                } catch (EntityNotFound) {
                }
            }

            $timeInSecondsPerWattage = null;
            if ($activity->getSportType()->getActivityType()->supportsPowerDistributionChart()) {
                $timeInSecondsPerWattage = $this->activityPowerRepository->findTimeInSecondsPerWattageForActivity($activity->getId());
            }

            $leafletMap = $activity->getLeafletMap();

            $this->filesystem->write(
                'build/html/activity/'.$activity->getId().'.html',
                $this->twig->load('html/activity/activity.html.twig')->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap ? [
                        'routes' => [$activity->getPolyline()],
                        'map' => $leafletMap,
                    ] : null,
                    'heartRateDistributionChart' => $timeInSecondsPerHeartRate && $activity->getAverageHeartRate() ? Json::encode(
                        HeartRateDistributionChartBuilder::fromHeartRateData(
                            heartRateData: $timeInSecondsPerHeartRate,
                            averageHeartRate: $activity->getAverageHeartRate(),
                            athleteMaxHeartRate: $athlete->getMaxHeartRate($activity->getStartDate())
                        )->build(),
                    ) : null,
                    'powerDistributionChart' => $timeInSecondsPerWattage && $activity->getAveragePower() ? Json::encode(
                        PowerDistributionChartBuilder::create(
                            powerData: $timeInSecondsPerWattage,
                            averagePower: $activity->getAveragePower(),
                        )->build(),
                    ) : null,
                    'segmentEfforts' => $this->segmentEffortRepository->findByActivityId($activity->getId()),
                    'splits' => $this->activitySplitRepository->findBy(
                        activityId: $activity->getId(),
                        unitSystem: $this->unitSystem
                    ),
                    'heartRateChart' => $heartRateStream?->getData() ? Json::encode(
                        HeartRateChartBuilder::create($heartRateStream)->build(),
                    ) : null,
                ]),
            );

            $dataDatableRows[] = DataTableRow::create(
                markup: $this->twig->load('html/activity/activity-data-table-row.html.twig')->render([
                    'timeIntervals' => ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                    'activity' => $activity,
                ]),
                searchables: $activity->getSearchables(),
                filterables: $activity->getFilterables(),
                sortValues: $activity->getSortables()
            );
        }

        $this->filesystem->write(
            'build/html/fetch-json/activity-data-table.json',
            Json::encode($dataDatableRows),
        );

        $command->getOutput()->writeln('  => Building error pages');
        $this->filesystem->write(
            'build/html/error/404.html',
            $this->twig->load('html/error/404.html.twig')->render(),
        );
        $this->filesystem->write(
            'build/html/error/50x.html',
            $this->twig->load('html/error/50x.html.twig')->render(),
        );
    }
}
