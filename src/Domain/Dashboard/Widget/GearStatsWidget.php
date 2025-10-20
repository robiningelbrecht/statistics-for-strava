<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypes;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGear;
use App\Domain\Gear\Gear;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Gears;
use App\Domain\Gear\MovingTimePerGearChart;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use Twig\Environment;

final readonly class GearStatsWidget implements Widget
{
    public function __construct(
        private GearRepository $gearRepository,
        private QueryBus $queryBus,
        private Clock $clock,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('includeRetiredGear', true)
            ->add('restrictToSportTypes', []);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('includeRetiredGear')) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" is required for GearStatsWidget.');
        }
        if (!is_bool($configuration->getConfigItem('includeRetiredGear'))) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" must be a boolean.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allYears = Years::all($this->clock->getCurrentDateTimeImmutable());
        $allGears = $this->gearRepository->findAll();

        if (!$configuration->getConfigItem('includeRetiredGear')) {
            $allGears = $allGears->filter(fn (Gear $gear): bool => !$gear->isRetired());
        }

        $gearsPerActivityType = [];
        foreach ($allGears as $gear) {
            foreach ($gear->getActivityTypes() as $activityType) {
                $gearsPerActivityType[$activityType->value] ??= Gears::empty();
                $gearsPerActivityType[$activityType->value]->add($gear);
            }
        }

        $chartsPerActivityType = [];
        foreach ($gearsPerActivityType as $activityType => $gearsForActivityType) {
            $movingTimePerGear = $this->queryBus->ask(new FindMovingTimePerGear(
                years: $allYears,
                activityTypes: ActivityTypes::fromArray([ActivityType::from($activityType)]),
            ))->getMovingTimePerGear();
            $chartsPerActivityType[$activityType] = Json::encode(MovingTimePerGearChart::create(
                movingTimePerGear: $movingTimePerGear,
                gears: $gearsForActivityType,
            )->build());
        }

        return $this->twig->load('html/dashboard/widget/widget--gear-stats.html.twig')->render([
            'chartAllGears' => Json::encode(MovingTimePerGearChart::create(
                movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($allYears, null))->getMovingTimePerGear(),
                gears: $allGears,
            )->build()),
            'chartsPerActivityType' => $chartsPerActivityType,
        ]);
    }
}
