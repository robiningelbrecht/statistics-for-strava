<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

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
            ->add('includeRetiredGear', true);
    }

    public function guardValidConfiguration(array $config): void
    {
        if (!array_key_exists('includeRetiredGear', $config)) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" is required for GearStatsWidget.');
        }
        if (!is_bool($config['includeRetiredGear'])) {
            throw new InvalidDashboardLayout('Configuration item "includeRetiredGear" must be a boolean.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allYears = Years::all($this->clock->getCurrentDateTimeImmutable());
        $gears = $this->gearRepository->findAll();

        if (!$configuration->getConfigItem('includeRetiredGear')) {
            $gears = $gears->filter(fn (Gear $gear) => !$gear->isRetired());
        }

        return $this->twig->load('html/dashboard/widget/widget--gear-stats.html.twig')->render([
            'gearChart' => Json::encode(MovingTimePerGearChart::create(
                movingTimePerGear: $this->queryBus->ask(new FindMovingTimePerGear($allYears))->getMovingTimePerGear(),
                gears: $gears,
            )->build()),
        ]);
    }
}
