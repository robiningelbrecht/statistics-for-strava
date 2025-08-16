<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Activity\Training\FindNumberOfRestDays\FindNumberOfRestDays;
use App\Domain\Activity\Training\TrainingLoadChart;
use App\Domain\Activity\Training\TrainingMetrics;
use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class TrainingLoadWidget implements Widget
{
    public function __construct(
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private ActivityIntensity $activityIntensity,
        private QueryBus $queryBus,
        private FilesystemOperator $buildStorage,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function guardValidConfiguration(array $config): void
    {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $timeInHeartRateZonesForLast30Days = $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZonesForLast30Days();

        $intensities = [];
        for ($i = (TrainingLoadChart::NUMBER_OF_DAYS_TO_DISPLAY + 210); $i >= 0; --$i) {
            $calculateForDate = $now->modify('- '.$i.' days');
            $intensities[$calculateForDate->format('Y-m-d')] = $this->activityIntensity->calculateForDate($calculateForDate);
        }

        $trainingMetrics = TrainingMetrics::create($intensities);

        $numberOfRestDays = $this->queryBus->ask(new FindNumberOfRestDays(DateRange::fromDates(
            from: $now->modify('-6 days'),
            till: $now,
        )))->getNumberOfRestDays();

        $this->buildStorage->write(
            'training-load.html',
            $this->twig->render('html/dashboard/training-load.html.twig', [
                'trainingLoadChart' => Json::encode(
                    TrainingLoadChart::create(
                        trainingMetrics: $trainingMetrics,
                        translator: $this->translator,
                        now: $now
                    )->build()
                ),
                'trainingMetrics' => $trainingMetrics,
                'restDaysInLast7Days' => $numberOfRestDays,
                'timeInHeartRateZonesForLast30Days' => $timeInHeartRateZonesForLast30Days,
            ])
        );

        return $this->twig->load('html/dashboard/widget/widget--training-load.html.twig')->render([
            'timeInHeartRateZonesForLast30Days' => $timeInHeartRateZonesForLast30Days,
            'trainingMetrics' => $trainingMetrics,
            'restDaysInLast7Days' => $numberOfRestDays,
        ]);
    }
}
