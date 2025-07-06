<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\ActivityIntensityChart;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\BestEffort\BestEffortChart;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsCharts;
use App\Domain\Strava\Activity\DistanceBreakdown;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\BestPowerOutputs;
use App\Domain\Strava\Activity\Stream\PowerOutputChart;
use App\Domain\Strava\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDays;
use App\Domain\Strava\Activity\Training\TrainingLoadChart;
use App\Domain\Strava\Activity\Training\TrainingMetrics;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStats;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStatsChart;
use App\Domain\Strava\Activity\WeeklyDistanceTimeChart;
use App\Domain\Strava\Activity\YearlyDistance\YearlyDistanceChart;
use App\Domain\Strava\Activity\YearlyDistance\YearlyStatistics;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZone;
use App\Domain\Strava\Athlete\HeartRateZone\TimeInHeartRateZoneChart;
use App\Domain\Strava\Athlete\Weight\AthleteWeightHistory;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\CarbonSavedComparison;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallengeCalculator;
use App\Domain\Strava\Challenge\Consistency\ConsistencyChallenges;
use App\Domain\Strava\Ftp\FtpHistory;
use App\Domain\Strava\Ftp\FtpHistoryChart;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\Years;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildDashboardHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private FtpHistory $ftpHistory,
        private AthleteWeightHistory $athleteWeightHistory,
        private ActivityTypeRepository $activityTypeRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private ActivityIntensity $activityIntensity,
        private ConsistencyChallenges $consistencyChallenges,
        private ConsistencyChallengeCalculator $consistencyChallengeCalculator,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildDashboardHtml);

        $now = $command->getCurrentDateTime();
        $importedActivityTypes = $this->activityTypeRepository->findAll();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();
        $allFtps = $this->ftpHistory->findAll();
        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );
        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $weekdayStats = WeekdayStats::create(
            activities: $allActivities,
            translator: $this->translator
        );
        $dayTimeStats = DaytimeStats::create($allActivities);

        $weeklyDistanceTimeCharts = [];
        $distanceBreakdowns = [];
        $yearlyDistanceCharts = [];
        $yearlyStatistics = [];

        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            if ($activityType->supportsWeeklyStats() && $chartData = WeeklyDistanceTimeChart::create(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem,
                activityType: $activityType,
                translator: $this->translator,
                now: $now,
            )->build()) {
                $weeklyDistanceTimeCharts[$activityType->value] = Json::encode($chartData);
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
                    YearlyDistanceChart::create(
                        activityRepository: $this->activityRepository,
                        uniqueYears: $activitiesPerActivityType[$activityType->value]->getUniqueYears(),
                        activityType: $activityType,
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

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightHistory->find($ftp->getSetOn())->getWeightInKg()
                );
            } catch (EntityNotFound) {
            }
        }

        $activityTotals = ActivityTotals::getInstance(
            activities: $allActivities,
            now: $now,
            translator: $this->translator,
        );
        $trivia = Trivia::getInstance($allActivities);
        $bestAllTimePowerOutputs = $this->activityPowerRepository->findBestForSportTypes(SportTypes::thatSupportPeakPowerOutputs());

        $bestEfforts = $bestEffortsCharts = [];
        /** @var ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if (!$activityType->supportsBestEffortsStats()) {
                continue;
            }

            $bestEffortsForActivityType = $this->activityBestEffortRepository->findBestEffortsFor($activityType);
            if ($bestEffortsForActivityType->isEmpty()) {
                continue;
            }

            $bestEfforts[$activityType->value] = $bestEffortsForActivityType;
            $bestEffortsCharts[$activityType->value] = Json::encode(
                BestEffortChart::create(
                    activityType: $activityType,
                    bestEfforts: $bestEffortsForActivityType,
                    sportTypes: $importedSportTypes,
                    translator: $this->translator,
                )->build()
            );
        }

        $intensities = [];
        for ($i = (TrainingLoadChart::NUMBER_OF_DAYS_TO_DISPLAY + 210); $i >= 0; --$i) {
            $calculateForDate = $now->modify('- '.$i.' days');
            $intensities[$calculateForDate->format('Y-m-d')] = $this->activityIntensity->calculateForDate($calculateForDate);
        }

        $trainingMetrics = TrainingMetrics::create($intensities);
        $numberOfRestDays = $this->queryBus->ask(new FindNumberOfRestDays(DateRange::fromDates(
            from: $now->modify('-6 days'),
            till: $now,
        )))->getNumberOfRestDays();

        $timeInHeartRateZoneData = TimeInHeartRateZoneChart::create(
            timeInSecondsInHeartRateZoneOne: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::ONE),
            timeInSecondsInHeartRateZoneTwo: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::TWO),
            timeInSecondsInHeartRateZoneThree: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::THREE),
            timeInSecondsInHeartRateZoneFour: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FOUR),
            timeInSecondsInHeartRateZoneFive: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FIVE),
            translator: $this->translator,
        );

        $this->buildStorage->write(
            'dashboard.html',
            $this->twig->load('html/dashboard/dashboard.html.twig')->render([
                'timeIntervals' => ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED,
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'intro' => $activityTotals,
                'weeklyDistanceCharts' => $weeklyDistanceTimeCharts,
                'powerOutputs' => $bestAllTimePowerOutputs,
                'activityIntensityChart' => Json::encode(
                    ActivityIntensityChart::create(
                        activityIntensity: $this->activityIntensity,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
                'weekdayStatsChart' => Json::encode(
                    WeekdayStatsChart::create($weekdayStats)->build(),
                ),
                'weekdayStats' => $weekdayStats,
                'daytimeStatsChart' => Json::encode(
                    DaytimeStatsCharts::create(
                        daytimeStats: $dayTimeStats,
                        translator: $this->translator,
                    )->build(),
                ),
                'daytimeStats' => $dayTimeStats,
                'distanceBreakdowns' => $distanceBreakdowns,
                'trivia' => $trivia,
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChart::create(
                        ftps: $allFtps,
                        now: $now
                    )->build()
                ) : null,
                'timeInHeartRateZoneChart' => Json::encode($timeInHeartRateZoneData->build()),
                'timeInHeartRateZoneData' => $timeInHeartRateZoneData,
                'allMonths' => $allMonths,
                'allConsistencyChallenges' => $this->consistencyChallenges,
                'calculatedConsistencyChallenges' => $this->consistencyChallengeCalculator->calculateFor(
                    months: $allMonths,
                    challenges: $this->consistencyChallenges
                ),
                'yearlyDistanceCharts' => $yearlyDistanceCharts,
                'yearlyStatistics' => $yearlyStatistics,
                'bestEfforts' => $bestEfforts,
                'bestEffortsCharts' => $bestEffortsCharts,
                'trainingMetrics' => $trainingMetrics,
                'restDaysInLast7Days' => $numberOfRestDays,
            ]),
        );

        $this->buildStorage->write(
            'training-load.html',
            $this->twig->render('html/dashboard/training-load.html.twig', [
                'trainingLoadChart' => Json::encode(
                    TrainingLoadChart::create(
                        trainingMetrics: $trainingMetrics,
                        translator: $this->translator,
                        now: $now
                    )->build()
                ),
                'trainingMetrics' => $trainingMetrics,
                'restDaysInLast7Days' => $numberOfRestDays,
                'timeInHeartRateZoneData' => $timeInHeartRateZoneData,
            ])
        );

        $this->buildStorage->write(
            'carbon-comparison.html',
            $this->twig->load('html/dashboard/carbon-comparison.html.twig')->render([
                'carbonSavedComparison' => CarbonSavedComparison::create($trivia->getTotalCarbonSaved()),
                'carbonSavedInKg' => $trivia->getTotalCarbonSaved(),
            ]),
        );

        if ($bestAllTimePowerOutputs->isEmpty()) {
            return;
        }

        $bestPowerOutputs = BestPowerOutputs::empty();
        $bestPowerOutputs->add(
            description: $this->translator->trans('All time'),
            powerOutputs: $bestAllTimePowerOutputs
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 45 days'),
            powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                dateRange: DateRange::lastXDays($now, 45)
            )
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 90 days'),
            powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                dateRange: DateRange::lastXDays($now, 90)
            )
        );
        foreach ($allYears->reverse() as $year) {
            $bestPowerOutputs->add(
                description: (string) $year,
                powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                    sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                    dateRange: $year->getRange(),
                )
            );
        }

        $this->buildStorage->write(
            'power-output.html',
            $this->twig->load('html/dashboard/power-output.html.twig')->render([
                'powerOutputChart' => Json::encode(
                    PowerOutputChart::create($bestPowerOutputs)->build()
                ),
                'bestPowerOutputs' => $bestPowerOutputs,
            ]),
        );
    }
}
