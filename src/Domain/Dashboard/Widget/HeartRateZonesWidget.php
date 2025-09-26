<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Athlete\HeartRateZone\TimeInHeartRateZoneChart;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class HeartRateZonesWidget implements Widget
{
    public function __construct(
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        return $this->twig->load('html/dashboard/widget/widget--heart-rate-zones.html.twig')->render([
            'timeInHeartRateZoneChart' => Json::encode(
                TimeInHeartRateZoneChart::create(
                    timeInHeartRateZones: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZones(),
                    translator: $this->translator,
                )->build(),
            ),
        ]);
    }
}
