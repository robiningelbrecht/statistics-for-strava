<?php

declare(strict_types=1);

namespace App\Application\Build\BuildIndexHtml;

use App\Application\Router;
use App\Domain\Athlete\Athlete;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Ramsey\Uuid\Uuid;
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

        $context = $this->indexHtml->getContext($command->getCurrentDateTime());
        $athlete = $context['athlete'];
        assert($athlete instanceof Athlete);

        $easterEggPageUrl = Uuid::uuid5(Uuid::NAMESPACE_DNS, $athlete->getAthleteId());
        $this->buildStorage->write(
            'index.html',
            $this->twig->load('html/index.html.twig')->render([
                'router' => Router::SINGLE_PAGE,
                'easterEggPageUrl' => $easterEggPageUrl,
                ...$context,
            ]),
        );

        $this->buildStorage->write(
            sprintf('%s.html', $easterEggPageUrl),
            $this->twig->load('html/easter-egg.html.twig')->render(),
        );
    }
}
