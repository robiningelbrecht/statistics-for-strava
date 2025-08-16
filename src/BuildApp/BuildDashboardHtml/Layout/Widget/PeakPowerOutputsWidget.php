<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Activity\Stream\ActivityPowerRepository;
use App\Domain\Activity\Stream\BestPowerOutputs;
use App\Domain\Activity\Stream\PowerOutputChart;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Years;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class PeakPowerOutputsWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private ActivityPowerRepository $activityPowerRepository,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(array $config): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        $bestAllTimePowerOutputs = $this->activityPowerRepository->findBestForSportTypes(SportTypes::thatSupportPeakPowerOutputs());
        if ($bestAllTimePowerOutputs->isEmpty()) {
            return null;
        }

        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $bestPowerOutputs = BestPowerOutputs::empty();
        $bestPowerOutputs->add(
            description: $this->translator->trans('All time'),
            powerOutputs: $bestAllTimePowerOutputs
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 45 days'),
            powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                dateRange: DateRange::lastXDays($now, 45)
            )
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 90 days'),
            powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                dateRange: DateRange::lastXDays($now, 90)
            )
        );
        foreach ($allYears->reverse() as $year) {
            $bestPowerOutputs->add(
                description: (string) $year,
                powerOutputs: $this->activityPowerRepository->findBestForSportTypesInDateRange(
                    sportTypes: SportTypes::thatSupportPeakPowerOutputs(),
                    dateRange: $year->getRange(),
                )
            );
        }

        $this->buildStorage->write(
            'power-output.html',
            $this->twig->load('html/dashboard/power-output.html.twig')->render([
                'powerOutputChart' => Json::encode(
                    PowerOutputChart::create($bestPowerOutputs)->build()
                ),
                'bestPowerOutputs' => $bestPowerOutputs,
            ]),
        );

        return $this->twig->load('html/dashboard/widget/widget--peak-power-outputs.html.twig')->render([
            'powerOutputs' => $bestAllTimePowerOutputs,
            'timeIntervals' => ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED,
        ]);
    }
}
