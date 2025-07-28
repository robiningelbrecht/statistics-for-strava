<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

use App\Domain\App\BuildDashboardHtml\Widget\Widgets;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildDashboardHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private Widgets $widgets,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildDashboardHtml);

        $this->buildStorage->write(
            'dashboard.html',
            $this->twig->load('html/dashboard/dashboard.html.twig')->render([
                'widgets' => $this->widgets,
                'now' => $command->getCurrentDateTime(),
            ]),
        );
    }
}
