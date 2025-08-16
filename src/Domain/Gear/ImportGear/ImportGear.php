<?php

namespace App\Domain\Gear\ImportGear;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ImportGear extends DomainCommand
{
    public function __construct(
        private OutputInterface $output,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
