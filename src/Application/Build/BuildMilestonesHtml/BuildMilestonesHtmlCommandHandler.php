<?php

declare(strict_types=1);

namespace App\Application\Build\BuildMilestonesHtml;

use App\Domain\Milestone\MilestoneCollector;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildMilestonesHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private MilestoneCollector $milestoneCollector,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildMilestonesHtml);

        $milestones = $this->milestoneCollector->discoverAll();

        $this->buildStorage->write(
            'milestones.html',
            $this->twig->load('html/milestones.html.twig')->render([
                'milestones' => $milestones,
            ]),
        );
    }
}
