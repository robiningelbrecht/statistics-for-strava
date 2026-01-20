<?php

declare(strict_types=1);

namespace App\Console\Webhook;

use App\Application\importDataAndBuildAppCronAction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:cron:process-webhooks', description: 'Process webhooks')]
final class ProcessWebhooksConsoleCommand extends Command
{
    public function __construct(
        private readonly importDataAndBuildAppCronAction $importDataAndBuildAppCronAction,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);
        $this->importDataAndBuildAppCronAction->runForWebhooks($output);

        return Command::SUCCESS;
    }
}
