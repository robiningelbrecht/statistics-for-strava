<?php

declare(strict_types=1);

namespace App\BuildApp\BuildApp;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class BuildApp extends DomainCommand
{
    public function __construct(
        private Application $consoleApplication,
        private SymfonyStyle $output,
    ) {
    }

    public function getConsoleApplication(): Application
    {
        return $this->consoleApplication;
    }

    public function getOutput(): SymfonyStyle
    {
        return $this->output;
    }
}
