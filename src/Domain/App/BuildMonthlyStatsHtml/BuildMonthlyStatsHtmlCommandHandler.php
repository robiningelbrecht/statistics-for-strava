<?php

declare(strict_types=1);

namespace App\Domain\App\BuildMonthlyStatsHtml;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\MonthlyStats\MonthlyStatsChart;
use App\Domain\Strava\Calendar\MonthlyStats\MonthlyStatsContext;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildMonthlyStatsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityRepository $activityRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private QueryBus $queryBus,
        private UnitSystem $unitSystem,
        private TranslatorInterface $translator,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildMonthlyStatsHtml);

        $now = $command->getCurrentDateTime();
        $allActivities = $this->activityRepository->findAll();
        $allChallenges = $this->challengeRepository->findAll();
        $activityTypes = $this->activityTypeRepository->findAll();

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $monthlyStats = $this->queryBus->ask(new FindMonthlyStats());

        $this->buildStorage->write(
            'monthly-stats.html',
            $this->twig->load('html/calendar/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStats,
                'challenges' => $allChallenges,
                'months' => $allMonths->reverse(),
                'sportTypes' => $this->sportTypeRepository->findAll(),
            ]),
        );

        $monthlyTimeStatCharts = [];
        foreach ($activityTypes as $activityType) {
            $monthlyTimeStatCharts[$activityType->value] = Json::encode(
                MonthlyStatsChart::create(
                    activityType: $activityType,
                    monthlyStats: $monthlyStats,
                    context: MonthlyStatsContext::TIME,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
        }

        $this->buildStorage->write(
            'monthly-stats/chart/time.html',
            $this->twig->load('html/calendar/monthly-charts.html.twig')->render([
                'monthlyStatsCharts' => $monthlyTimeStatCharts,
                'context' => MonthlyStatsContext::TIME,
            ]),
        );

        $monthlyDistanceStatCharts = [];
        foreach ($activityTypes as $activityType) {
            $monthlyDistanceStatCharts[$activityType->value] = Json::encode(
                MonthlyStatsChart::create(
                    activityType: $activityType,
                    monthlyStats: $monthlyStats,
                    context: MonthlyStatsContext::DISTANCE,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
        }

        $this->buildStorage->write(
            'monthly-stats/chart/distance.html',
            $this->twig->load('html/calendar/monthly-charts.html.twig')->render([
                'monthlyStatsCharts' => $monthlyDistanceStatCharts,
                'context' => MonthlyStatsContext::DISTANCE,
            ]),
        );

        /** @var Month $month */
        foreach ($allMonths as $month) {
            $this->buildStorage->write(
                'month/month-'.$month->getId().'.html',
                $this->twig->load('html/calendar/month.html.twig')->render([
                    'hasPreviousMonth' => $month->getId() != $allActivities->getFirstActivityStartDate()->format(Month::MONTH_ID_FORMAT),
                    'hasNextMonth' => $month->getId() != $now->format(Month::MONTH_ID_FORMAT),
                    'statistics' => $monthlyStats->getForMonth($month),
                    'challenges' => $allChallenges,
                    'calendar' => Calendar::create(
                        month: $month,
                        activityRepository: $this->activityRepository,
                    ),
                ]),
            );
        }
    }
}
