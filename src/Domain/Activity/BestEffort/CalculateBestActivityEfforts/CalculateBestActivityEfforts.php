<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort\CalculateBestActivityEfforts;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class CalculateBestActivityEfforts extends DomainCommand
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
