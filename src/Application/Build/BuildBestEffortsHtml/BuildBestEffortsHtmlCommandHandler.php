<?php

declare(strict_types=1);

namespace App\Application\Build\BuildBestEffortsHtml;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\BestEffort\BestEffortChart;
use App\Domain\Activity\BestEffort\BestEffortPeriod;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildBestEffortsHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityTypeRepository $activityTypeRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private TranslatorInterface $translator,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildBestEffortsHtml);

        $bestEfforts = $bestEffortsCharts = [];

        $now = $this->clock->getCurrentDateTimeImmutable();
        $importedActivityTypes = $this->activityTypeRepository->findAll();
        $importedSportTypes = $this->sportTypeRepository->findAll();

        /** @var ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if (!$activityType->supportsBestEffortsStats()) {
                continue;
            }

            foreach (BestEffortPeriod::cases() as $bestEffortPeriod) {
                $bestEffortsForActivityTypeAndPeriod = $this->activityBestEffortRepository->findBestEffortsFor(
                    activityType: $activityType,
                    dateRange: $bestEffortPeriod->getDateRange($now)
                );
                if ($bestEffortsForActivityTypeAndPeriod->isEmpty()) {
                    continue;
                }

                $bestEfforts[$activityType->value][$bestEffortPeriod->value] = $bestEffortsForActivityTypeAndPeriod;
                $bestEffortsCharts[$activityType->value][$bestEffortPeriod->value] = Json::encode(
                    BestEffortChart::create(
                        activityType: $activityType,
                        bestEfforts: $bestEffortsForActivityTypeAndPeriod,
                        sportTypes: $importedSportTypes,
                        translator: $this->translator,
                    )->build()
                );
            }

            $bestEffortsHistoryForActivityType = $this->activityBestEffortRepository->findBestEffortHistory($activityType);
            if ($bestEffortsHistoryForActivityType->isEmpty()) {
                continue;
            }

            foreach ($activityType->getDistancesForBestEffortCalculation() as $distance) {
                $this->buildStorage->write(
                    strtolower(sprintf(
                        'best-efforts/%s/%s-%s.html',
                        $activityType->value,
                        $distance->toInt(),
                        $distance->getSymbol()
                    )),
                    $this->twig->load('html/best-efforts/best-efforts-history.html.twig')->render([
                        'activityType' => $activityType,
                        'bestEffortsHistory' => $bestEffortsHistoryForActivityType,
                        'distance' => $distance,
                    ])
                );
            }
        }

        if (empty($bestEffortsCharts)) {
            return;
        }

        $this->buildStorage->write(
            'best-efforts.html',
            $this->twig->load('html/best-efforts/best-efforts.html.twig')->render([
                'bestEfforts' => $bestEfforts,
                'bestEffortsCharts' => $bestEffortsCharts,
            ])
        );
    }
}
