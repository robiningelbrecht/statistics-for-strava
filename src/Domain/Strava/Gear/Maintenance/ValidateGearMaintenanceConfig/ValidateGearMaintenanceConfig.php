<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\ValidateGearMaintenanceConfig;

use App\Infrastructure\CQRS\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ValidateGearMaintenanceConfig extends DomainCommand
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
