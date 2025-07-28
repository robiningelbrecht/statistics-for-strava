<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Widget;

use App\Domain\Strava\Athlete\Weight\AthleteWeightHistory;
use App\Domain\Strava\Ftp\FtpHistory;
use App\Domain\Strava\Ftp\FtpHistoryChart;
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

    public function render(SerializableDateTime $now): ?string
    {
        $allFtps = $this->ftpHistory->findAll();
        if ($allFtps->isEmpty()) {
            return null;
        }

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightHistory->find($ftp->getSetOn())->getWeightInKg()
                );
            } catch (EntityNotFound) {
            }
        }

        return $this->twig->load('html/dashboard/widget/ftp-history.html.twig')->render([
            'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                FtpHistoryChart::create(
                    ftps: $allFtps,
                    now: $now
                )->build()
            ) : null,
        ]);
    }
}
