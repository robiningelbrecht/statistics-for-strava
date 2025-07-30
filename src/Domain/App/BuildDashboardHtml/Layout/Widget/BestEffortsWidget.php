<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\BestEffort\BestEffortChart;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BestEffortsWidget implements Widget
{
    public function __construct(
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private SportTypeRepository $sportTypeRepository,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        $bestEfforts = $bestEffortsCharts = [];

        $importedActivityTypes = $this->activityTypeRepository->findAll();
        $importedSportTypes = $this->sportTypeRepository->findAll();

        /** @var ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if (!$activityType->supportsBestEffortsStats()) {
                continue;
            }

            $bestEffortsForActivityType = $this->activityBestEffortRepository->findBestEffortsFor($activityType);
            if ($bestEffortsForActivityType->isEmpty()) {
                continue;
            }

            $bestEfforts[$activityType->value] = $bestEffortsForActivityType;
            $bestEffortsCharts[$activityType->value] = Json::encode(
                BestEffortChart::create(
                    activityType: $activityType,
                    bestEfforts: $bestEffortsForActivityType,
                    sportTypes: $importedSportTypes,
                    translator: $this->translator,
                )->build()
            );
        }

        if (empty($bestEffortsCharts)) {
            return null;
        }

        return $this->twig->load('html/dashboard/widget/widget--best-efforts.html.twig')->render([
            'bestEfforts' => $bestEfforts,
            'bestEffortsCharts' => $bestEffortsCharts,
        ]);
    }
}
