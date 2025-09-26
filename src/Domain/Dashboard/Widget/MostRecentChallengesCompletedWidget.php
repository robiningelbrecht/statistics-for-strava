<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Challenge\ChallengeRepository;
use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Twig\Environment;

final readonly class MostRecentChallengesCompletedWidget implements Widget
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private Environment $twig,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty()
            ->add('numberOfChallengesToDisplay', 5);
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
        if (!$configuration->configItemExists('numberOfChallengesToDisplay')) {
            throw new InvalidDashboardLayout('Configuration item "numberOfChallengesToDisplay" is required for MostRecentChallengesCompletedWidget.');
        }
        if (!is_int($configuration->getConfigItem('numberOfChallengesToDisplay'))) {
            throw new InvalidDashboardLayout('Configuration item "numberOfChallengesToDisplay" must be an integer.');
        }
        if ($configuration->getConfigItem('numberOfChallengesToDisplay') < 1) {
            throw new InvalidDashboardLayout('Configuration item "numberOfChallengesToDisplay" must be set to a value of 1 or greater.');
        }
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        $challenges = $this->challengeRepository->findAll();
        if ($challenges->isEmpty()) {
            return null;
        }

        /** @var int $numberOfChallengesToDisplay */
        $numberOfChallengesToDisplay = $configuration->getConfigItem('numberOfChallengesToDisplay');

        return $this->twig->load('html/dashboard/widget/widget--most-recent-challenges.html.twig')->render([
            'challenges' => $challenges->slice(0, $numberOfChallengesToDisplay),
        ]);
    }
}
