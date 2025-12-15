<?php

declare(strict_types=1);

namespace App\Application\RunImport;

use App\Domain\Activity\ActivityIds;
use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class RunImport extends DomainCommand
{
    public function __construct(
        private SymfonyStyle $output,
        private ?ActivityIds $restrictToActivityIds,
    ) {
    }

    public function getOutput(): SymfonyStyle
    {
        return $this->output;
    }

    public function getRestrictToActivityIds(): ?ActivityIds
    {
        return $this->restrictToActivityIds;
    }
}
