<?php

declare(strict_types=1);

namespace App\Application\Build\BuildIndexHtml;

use App\Application\Router;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildIndexHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private IndexHtml $indexHtml,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildIndexHtml);

        $this->buildStorage->write(
            'index.html',
            $this->twig->load('html/index.html.twig')->render([
                'router' => Router::SINGLE_PAGE,
                ...$this->indexHtml->getContext($command->getCurrentDateTime()),
            ]),
        );
    }
}
