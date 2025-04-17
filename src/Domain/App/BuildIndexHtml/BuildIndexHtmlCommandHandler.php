<?php

declare(strict_types=1);

namespace App\Domain\App\BuildIndexHtml;

use App\Domain\App\ProfilePictureUrl;
use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

final readonly class BuildIndexHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private ImageRepository $imageRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
        private ?ProfilePictureUrl $profilePictureUrl,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private LocaleSwitcher $localeSwitcher,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildIndexHtml);

        $athlete = $this->athleteRepository->find();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $eddingtonNumbers = [];
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
            $eddingtonNumbers[] = $eddington->getNumber();
        }

        $this->buildStorage->write(
            'index.html',
            $this->twig->load('html/index.html.twig')->render([
                'totalActivityCount' => $this->activityRepository->count(),
                'eddingtonNumbers' => $eddingtonNumbers,
                'completedChallenges' => $this->challengeRepository->count(),
                'totalPhotoCount' => $this->imageRepository->count(),
                'lastUpdate' => $command->getCurrentDateTime(),
                'athlete' => $athlete,
                'profilePictureUrl' => $this->profilePictureUrl,
                'maintenanceTaskIsDue' => $this->maintenanceTaskProgressCalculator->calculateIfATaskIsDue(),
                'javascriptWindowConstants' => Json::encode([
                    'countries' => Countries::getNames($this->localeSwitcher->getLocale()),
                    'unitSystem' => [
                        'paceSymbol' => $this->unitSystem->paceSymbol(),
                    ],
                ]),
            ]),
        );
    }
}
