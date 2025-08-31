<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Athlete\Weight\AthleteWeightHistory;
use App\Domain\Ftp\FtpHistory;
use App\Domain\Ftp\FtpHistoryChart;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class FtpHistoryWidget implements Widget
{
    public function __construct(
        private FtpHistory $ftpHistory,
        private AthleteWeightHistory $athleteWeightHistory,
        private Environment $twig,
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
        $allFtps = $this->ftpHistory->findAll();
        if ($allFtps->isEmpty()) {
            return null;
        }

        /** @var \App\Domain\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightHistory->find($ftp->getSetOn())->getWeightInKg()
                );
            } catch (EntityNotFound) {
            }
        }

        return $this->twig->load('html/dashboard/widget/widget--ftp-history.html.twig')->render([
            'ftpHistoryChart' => Json::encode(
                FtpHistoryChart::create(
                    ftps: $allFtps,
                    now: $now
                )->build()
            ),
        ]);
    }
}
