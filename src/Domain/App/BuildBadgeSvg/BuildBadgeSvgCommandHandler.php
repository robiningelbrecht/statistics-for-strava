<?php

declare(strict_types=1);

namespace App\Domain\App\BuildBadgeSvg;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Trivia;
use App\Domain\Zwift\ZwiftLevel;
use App\Domain\Zwift\ZwiftRacingScore;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildBadgeSvgCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ChallengeRepository $challengeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private ?ZwiftLevel $zwiftLevel,
        private ?ZwiftRacingScore $zwiftRacingScore,
        private Environment $twig,
        private FilesystemOperator $fileStorage,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildBadgeSvg);

        $now = $command->getCurrentDateTime();
        $athlete = $this->athleteRepository->find();
        $activities = $this->activitiesEnricher->getEnrichedActivities();

        $activityTotals = ActivityTotals::getInstance(
            activities: $activities,
            now: $now,
            translator: $this->translator
        );
        $trivia = Trivia::getInstance($activities);

        $this->fileStorage->write(
            'strava-badge.svg',
            $this->twig->load('svg/badge/svg-strava-badge.html.twig')->render([
                'athlete' => $athlete,
                'activities' => $activities->slice(0, 5),
                'activityTotals' => $activityTotals,
                'trivia' => $trivia,
                'challengesCompleted' => $this->challengeRepository->count(),
            ])
        );

        if ($this->zwiftLevel) {
            $this->fileStorage->write(
                'zwift-badge.svg',
                $this->twig->load('svg/badge/svg-zwift-badge.html.twig')->render([
                    'athlete' => $athlete,
                    'zwiftLevel' => $this->zwiftLevel,
                    'zwiftRacingScore' => $this->zwiftRacingScore,
                ])
            );
        }
        $this->buildStorage->write(
            'badge.html',
            $this->twig->load('html/badge.html.twig')->render([
                'zwiftLevel' => $this->zwiftLevel,
            ]),
        );
    }
}
