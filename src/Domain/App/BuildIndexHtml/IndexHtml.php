<?php

declare(strict_types=1);

namespace App\Domain\App\BuildIndexHtml;

use App\Domain\App\AppSubTitle;
use App\Domain\App\ProfilePictureUrl;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Eddington\Config\EddingtonConfiguration;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
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
        private EddingtonConfiguration $eddingtonConfiguration,
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
        /** @var \App\Domain\Strava\Activity\Eddington\Config\EddingtonConfigItem $eddingtonConfigItem */
        foreach ($this->eddingtonConfiguration as $eddingtonConfigItem) {
            $activities = $this->activityRepository->findBySportTypes($eddingtonConfigItem->getSportTypesToInclude());
            if ($activities->isEmpty()) {
                continue;
            }
            $eddington = Eddington::getInstance(
                activities: $activities,
                config: $eddingtonConfigItem,
                unitSystem: $this->unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            if (!$eddingtonConfigItem->showInNavBar()) {
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
                ],
            ]),
        ];
    }
}
