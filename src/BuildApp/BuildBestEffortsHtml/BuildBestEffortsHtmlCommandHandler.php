<?php

declare(strict_types=1);

namespace App\BuildApp\BuildBestEffortsHtml;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Activity\BestEffort\BestEffortChart;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Serialization\Json;
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
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildBestEffortsHtml);

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
            return;
        }

        $this->buildStorage->write(
            'best-efforts.html',
            $this->twig->load('html/best-efforts.html.twig')->render([
                'bestEfforts' => $bestEfforts,
                'bestEffortsCharts' => $bestEffortsCharts,
            ])
        );
    }
}
