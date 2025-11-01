<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivityTypeRepository;
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
        private ActivityTypeRepository $activityTypeRepository,
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
        if (!$configuration->exists('includeRetiredGear')) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" is required for GearStatsWidget.');
        }
        if (!is_bool($configuration->get('includeRetiredGear'))) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" must be a boolean.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allYears = Years::all($this->clock->getCurrentDateTimeImmutable());
        $allUsedGears = $this->gearRepository->findAllUsed();
        $importedActivityTypes = $this->activityTypeRepository->findAll();

        if (!$configuration->get('includeRetiredGear')) {
            $allUsedGears = $allUsedGears->filter(fn (Gear $gear): bool => !$gear->isRetired());
        }

        $gearsPerActivityType = [];
        foreach ($allUsedGears as $gear) {
            foreach ($gear->getActivityTypes() as $activityType) {
                $gearsPerActivityType[$activityType->value] ??= Gears::empty();
                $gearsPerActivityType[$activityType->value]->add($gear);
            }
        }

        $chartsPerActivityType = [];
        if (count($gearsPerActivityType) > 1) {
            /** @var \App\Domain\Activity\ActivityType $activityType */
            foreach ($importedActivityTypes as $activityType) {
                if (!isset($gearsPerActivityType[$activityType->value])) {
                    continue;
                }
                $movingTimePerGear = $this->queryBus->ask(new FindMovingTimePerGear(
                    years: $allYears,
                    activityTypes: ActivityTypes::fromArray([$activityType]),
                ))->getMovingTimePerGear();
                $chartsPerActivityType[$activityType->value] = Json::encode(MovingTimePerGearChart::create(
                    movingTimePerGear: $movingTimePerGear,
                    gears: $gearsPerActivityType[$activityType->value],
                )->build());
            }
        }

        return $this->twig->load('html/dashboard/widget/widget--gear-stats.html.twig')->render([
            'chartAllGears' => Json::encode(MovingTimePerGearChart::create(
                movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($allYears, null))->getMovingTimePerGear(),
                gears: $allUsedGears,
            )->build()),
            'chartsPerActivityType' => $chartsPerActivityType,
        ]);
    }
}
