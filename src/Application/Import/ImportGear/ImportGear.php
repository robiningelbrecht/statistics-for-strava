<?php

namespace App\Application\Import\ImportGear;

use App\Domain\Activity\ActivityIds;
use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ImportGear extends DomainCommand
{
    public function __construct(
        private OutputInterface $output,
        private ?ActivityIds $restrictToActivityIds,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getRestrictToActivityIds(): ActivityIds
    {
        return $this->restrictToActivityIds ?? ActivityIds::empty();
    }

    public function isPartialImport(): bool
    {
        return !$this->getRestrictToActivityIds()->isEmpty();
    }
}
