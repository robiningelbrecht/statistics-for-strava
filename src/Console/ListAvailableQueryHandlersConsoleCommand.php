<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\CQRS\Query\Bus\QueryBus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:query-bus:handlers', description: 'List all available query handlers')]
final class ListAvailableQueryHandlersConsoleCommand extends Command
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->queryBus->getAvailableQueryHandlers() as $queryHandler) {
            $output->writeln($queryHandler);
        }

        return Command::SUCCESS;
    }
}
