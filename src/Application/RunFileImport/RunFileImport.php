<?php

declare(strict_types=1);

namespace App\Application\RunFileImport;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class RunFileImport extends DomainCommand
{
    public function __construct(
        private SymfonyStyle $output,
    ) {
    }

    public function getOutput(): SymfonyStyle
    {
        return $this->output;
    }
}
