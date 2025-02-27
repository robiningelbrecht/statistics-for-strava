<?php

declare(strict_types=1);

namespace App\Domain\App\BuildMonthlyStatsHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildMonthlyStatsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildMonthlyStatsHtml);

        $now = $command->getCurrentDateTime();
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $allChallenges = $this->challengeRepository->findAll();

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );

        $monthlyStatistics = MonthlyStatistics::create(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );

        $this->filesystem->write(
            'build/html/monthly-stats.html',
            $this->twig->load('html/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStatistics,
                'sportTypes' => $this->sportTypeRepository->findAll(),
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
    }
}
