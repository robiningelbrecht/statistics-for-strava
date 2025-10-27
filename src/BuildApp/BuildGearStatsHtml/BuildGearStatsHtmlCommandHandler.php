<?php

declare(strict_types=1);

namespace App\BuildApp\BuildGearStatsHtml;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Calendar\Months;
use App\Domain\Gear\CustomGear\CustomGearConfig;
use App\Domain\Gear\DistanceOverTimePerGearChart;
use App\Domain\Gear\DistancePerMonthPerGearChart;
use App\Domain\Gear\FindGearStatsPerDay\FindGearStatsPerDay;
use App\Domain\Gear\Gear;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\GearStatistics;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ImportedGear\ImportedGearConfig;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
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
        private ImportedGearConfig $importedGearConfig,
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
            endDate: $now
        );

        /** @var Gear $gear */
        foreach ($allGear as $gear) {
            if (GearType::CUSTOM === $gear->getType()) {
                // Already enriched in CustomGearConfig.
                continue;
            }
            $this->importedGearConfig->enrichGearWithCustomData($gear);
        }

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
