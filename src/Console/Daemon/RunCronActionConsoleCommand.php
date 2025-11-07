<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Infrastructure\Cron\RunnableCronAction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(name: 'app:cron:action', description: 'Run a cron action')]
final class RunCronActionConsoleCommand extends Command
{
    /**
     * @param iterable<RunnableCronAction> $runnableCronActions
     */
    public function __construct(
        #[AutowireIterator('app.cron_action')]
        private readonly iterable $runnableCronActions,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('cronActionId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runnableCronActionId = $input->getArgument('cronActionId');
        foreach ($this->runnableCronActions as $runnableCronAction) {
            if ($runnableCronAction->getId() !== $input->getArgument('cronActionId')) {
                continue;
            }
            $runnableCronAction->run($output);

            return Command::SUCCESS;
        }

        throw new \RuntimeException(sprintf('Invalid cronActionId "%s"', $runnableCronActionId));
    }
}
