<?php

declare(strict_types=1);

namespace App\Application\Build\BuildChallengesHtml;

use App\Domain\Challenge\ChallengeRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildChallengesHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildChallengesHtml);

        $challenges = $this->challengeRepository->findAll();

        $challengesGroupedByMonth = [];
        foreach ($challenges as $challenge) {
            $challengesGroupedByMonth[$challenge->getCreatedOn()->translatedFormat('F Y')][] = $challenge;
        }
        $this->buildStorage->write(
            'challenges.html',
            $this->twig->load('html/challenges.html.twig')->render([
                'challengesGroupedPerMonth' => $challengesGroupedByMonth,
            ]),
        );
    }
}
