<?php

declare(strict_types=1);

namespace App\BuildApp\BuildIndexHtml;

use App\BuildApp\AppSubTitle;
use App\BuildApp\ProfilePictureUrl;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\Eddington\EddingtonCalculator;
use App\Domain\Activity\Image\ImageRepository;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Challenge\ChallengeRepository;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class IndexHtml
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private ImageRepository $imageRepository,
        private EddingtonCalculator $eddingtonCalculator,
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
        private ?ProfilePictureUrl $profilePictureUrl,
        private ?AppSubTitle $appSubTitle,
        private UnitSystem $unitSystem,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(SerializableDateTime $now): array
    {
        $eddingtonNumbers = [];
        $eddingtons = $this->eddingtonCalculator->calculate();

        foreach ($eddingtons as $eddington) {
            if (!$eddington->getConfig()->showInNavBar()) {
                continue;
            }
            $eddingtonNumbers[] = $eddington->getNumber();
        }

        return [
            'totalActivityCount' => $this->activityRepository->count(),
            'eddingtonNumbers' => $eddingtonNumbers,
            'completedChallenges' => $this->challengeRepository->count(),
            'totalPhotoCount' => $this->imageRepository->count(),
            'lastUpdate' => $now,
            'athlete' => $this->athleteRepository->find(),
            'profilePictureUrl' => $this->profilePictureUrl,
            'subTitle' => $this->appSubTitle,
            'maintenanceTaskIsDue' => !$this->maintenanceTaskProgressCalculator->getGearIdsThatHaveDueTasks()->isEmpty(),
            'javascriptWindowConstants' => Json::encode([
                'countries' => Countries::getNames($this->localeSwitcher->getLocale()),
                'unitSystem' => [
                    'paceSymbol' => $this->unitSystem->paceSymbol(),
                    'distanceSymbol' => $this->unitSystem->distanceSymbol(),
                    'elevationSymbol' => $this->unitSystem->elevationSymbol(),
                ],
            ]),
        ];
    }
}
