<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear\LinkCustomGearToActivities;

use App\Infrastructure\CQRS\Command\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class LinkCustomGearToActivities extends DomainCommand
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
