<?php

declare(strict_types=1);

namespace App\Domain\App\BuildEddingtonHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonChart;
use App\Domain\Strava\Activity\Eddington\EddingtonHistoryChart;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildEddingtonHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildEddingtonHtml);

        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $eddingtonPerActivityType = [];
        foreach ($activitiesPerActivityType as $activityType => $activities) {
            $activityType = ActivityType::from($activityType);
            if (!$activityType->supportsEddington()) {
                continue;
            }
            if ($activities->isEmpty()) {
                continue;
            }
            $eddington = Eddington::getInstance(
                activities: $activities,
                activityType: $activityType,
                unitSystem: $this->unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            $eddingtonPerActivityType[$activityType->value] = $eddington;
        }

        $eddingtonChartsPerActivityType = [];
        $eddingtonHistoryChartsPerActivityType = [];
        foreach ($eddingtonPerActivityType as $activityType => $eddington) {
            $eddingtonChartsPerActivityType[$activityType] = Json::encode(
                EddingtonChart::create(
                    eddington: $eddington,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
            $eddingtonHistoryChartsPerActivityType[$activityType] = Json::encode(
                EddingtonHistoryChart::create(
                    eddington: $eddington,
                )->build()
            );
        }

        $this->buildStorage->write(
            'eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'eddingtons' => $eddingtonPerActivityType,
                'eddingtonCharts' => $eddingtonChartsPerActivityType,
                'eddingtonHistoryCharts' => $eddingtonHistoryChartsPerActivityType,
                'distanceUnit' => Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol(),
            ]),
        );
    }
}
