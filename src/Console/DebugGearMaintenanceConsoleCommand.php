<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:gear-maintenance', description: 'Outputs gear maintenance debugging info')]
final class DebugGearMaintenanceConsoleCommand extends Command
{
    public function __construct(
        private readonly GearMaintenanceConfig $gearMaintenanceConfig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Statistics for Strava');
        $io->text('Please copy all this output into the description of the bug ticket');
        $io->warning('Do not forget to redact sensitive information');

        $io->newLine();

        $io->writeln((string) $this->gearMaintenanceConfig);

        return Command::SUCCESS;
    }
}
