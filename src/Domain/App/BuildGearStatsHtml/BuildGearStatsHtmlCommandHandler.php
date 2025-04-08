<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGearStatsHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChart;
use App\Domain\Strava\Gear\DistancePerMonthPerGearChart;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\Gear\GearStatsRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildGearStatsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
        private GearStatsRepository $gearStatsRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildGearStatsHtml);

        $now = $command->getCurrentDateTime();
        $activities = $this->activitiesEnricher->getEnrichedActivities();
        $allGear = $this->gearRepository->findAll();
        $gearStats = $this->gearStatsRepository->findStatsPerGearIdPerDay();
        $allMonths = Months::create(
            startDate: $activities->getFirstActivityStartDate(),
            now: $now
        );

        $this->buildStorage->write(
            'gear-stats.html',
            $this->twig->load('html/gear/gear-stats.html.twig')->render([
                'gearStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $activities,
                    bikes: $allGear
                ),
                'distancePerMonthPerGearChart' => Json::encode(
                    DistancePerMonthPerGearChart::create(
                        gearCollection: $allGear,
                        activityCollection: $activities,
                        unitSystem: $this->unitSystem,
                        months: $allMonths,
                    )->build()
                ),
                'distanceOverTimePerGear' => Json::encode(
                    DistanceOverTimePerGearChart::create(
                        gears: $allGear,
                        gearStats: $gearStats,
                        startDate: $activities->getFirstActivityStartDate(),
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
            ]),
        );
    }
}
