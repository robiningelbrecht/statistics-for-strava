<?php

declare(strict_types=1);

namespace App\BuildApp\BuildMonthlyStatsHtml;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Calendar\Calendar;
use App\Domain\Calendar\FindMonthlyStats\FindMonthlyStats;
use App\Domain\Calendar\Month;
use App\Domain\Calendar\MonthlyStats\MonthlyStatsChart;
use App\Domain\Calendar\MonthlyStats\MonthlyStatsContext;
use App\Domain\Calendar\Months;
use App\Domain\Challenge\ChallengeRepository;
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

        foreach (MonthlyStatsContext::cases() as $monthlyStatsContext) {
            $monthlyStatCharts = [];
            foreach ($activityTypes as $activityType) {
                $monthlyStatCharts[$activityType->value] = Json::encode(
                    MonthlyStatsChart::create(
                        activityType: $activityType,
                        monthlyStats: $monthlyStats,
                        context: $monthlyStatsContext,
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                    )->build()
                );
            }

            $this->buildStorage->write(
                sprintf('monthly-stats/chart/%s.html', $monthlyStatsContext->value),
                $this->twig->load('html/calendar/monthly-charts.html.twig')->render([
                    'monthlyStatsCharts' => $monthlyStatCharts,
                    'context' => $monthlyStatsContext,
                ]),
            );
        }
    }
}
