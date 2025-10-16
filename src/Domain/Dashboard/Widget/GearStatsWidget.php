<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Gear\FindMovingTimePerGear\FindMovingTimePerGear;
use App\Domain\Gear\Gear;
use App\Domain\Gear\GearRepository;
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
        if (!$configuration->configItemExists('restrictToSportTypes')) {
            throw new InvalidDashboardLayout('Configuration item "restrictToSportTypes" is required for GearStatsWidget.');
        }
        if (!is_array($configuration->getConfigItem('restrictToSportTypes'))) {
            throw new InvalidDashboardLayout('Configuration item "restrictToSportTypes" must be an array.');
        }

        $sportTypes = $configuration->getConfigItem('restrictToSportTypes');
        foreach ($sportTypes as $sportType) {
            if (!SportType::tryFrom($sportType)) {
                throw new InvalidDashboardLayout(sprintf('Configuration item "restrictToSportTypes" has an invalid sport type %s.', $sportType));
            }
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allYears = Years::all($this->clock->getCurrentDateTimeImmutable());
        $gears = $this->gearRepository->findAll();

        if (!$configuration->getConfigItem('includeRetiredGear')) {
            $gears = $gears->filter(fn (Gear $gear): bool => !$gear->isRetired());
        }

        if ($sportTypesToRestrictTo = $configuration->getConfigItem('restrictToSportTypes')) {
            $sportTypes = SportTypes::fromArray(array_map(
                fn (string $sportType): SportType => SportType::from($sportType),  // @phpstan-ignore argument.type
                $sportTypesToRestrictTo // @phpstan-ignore argument.type
            ));
            $gears = $gears->filter(fn (Gear $gear): bool => $gear->hasAtLeastOneSportType($sportTypes));
        }

        return $this->twig->load('html/dashboard/widget/widget--gear-stats.html.twig')->render([
            'gearChart' => Json::encode(MovingTimePerGearChart::create(
                movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($allYears))->getMovingTimePerGear(),
                gears: $gears,
            )->build()),
        ]);
    }
}
