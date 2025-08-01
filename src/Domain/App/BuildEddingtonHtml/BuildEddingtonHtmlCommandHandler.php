<?php

declare(strict_types=1);

namespace App\Domain\App\BuildEddingtonHtml;

use App\Domain\Strava\Activity\Eddington\EddingtonCalculator;
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
        private EddingtonCalculator $eddingtonCalculator,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildEddingtonHtml);

        $eddingtons = $this->eddingtonCalculator->calculate();

        $eddingtonCharts = [];
        $eddingtonHistoryCharts = [];
        foreach ($eddingtons as $id => $eddington) {
            $eddingtonCharts[$id] = Json::encode(
                EddingtonChart::create(
                    eddington: $eddington,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
            $eddingtonHistoryCharts[$id] = Json::encode(
                EddingtonHistoryChart::create(
                    eddington: $eddington,
                )->build()
            );
        }

        $this->buildStorage->write(
            'eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'eddingtons' => $eddingtons,
                'eddingtonCharts' => $eddingtonCharts,
                'eddingtonHistoryCharts' => $eddingtonHistoryCharts,
                'distanceUnit' => Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol(),
            ]),
        );
    }
}
