<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGearStatsHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Gear\CustomGear\CustomGearConfig;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChart;
use App\Domain\Strava\Gear\DistancePerMonthPerGearChart;
use App\Domain\Strava\Gear\FindGearStatsPerDay\FindGearStatsPerDay;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildGearStatsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
        private CustomGearConfig $customGearConfig,
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
        private QueryBus $queryBus,
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
        $gearStats = $this->queryBus->ask(new FindGearStatsPerDay());
        $allMonths = Months::create(
            startDate: $activities->getFirstActivityStartDate(),
            now: $now
        );

        $this->buildStorage->write(
            'gear.html',
            $this->twig->load('html/gear/gear.html.twig')->render([
                'maintenanceTaskIsDue' => !$this->maintenanceTaskProgressCalculator->getGearIdsThatHaveDueTasks()->isEmpty(),
                'customGearConfig' => $this->customGearConfig,
                'gearStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $activities,
                    gears: $allGear
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
