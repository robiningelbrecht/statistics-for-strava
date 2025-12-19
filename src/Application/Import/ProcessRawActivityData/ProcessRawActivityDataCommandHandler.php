<?php

namespace App\Application\Import\ProcessRawActivityData;

use App\Application\Import\ProcessRawActivityData\Pipeline\ProcessRawDataStep;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ProcessRawActivityDataCommandHandler implements CommandHandler
{
    /**
     * @param iterable<ProcessRawDataStep> $steps
     */
    public function __construct(
        #[AutowireIterator('app.activity_process_raw_data.pipeline_step')]
        private iterable $steps,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ProcessRawActivityData);

        $command->getOutput()->writeln('Processing raw activity data...');

        foreach ($this->steps as $step) {
            $step->process($command->getOutput());
        }
    }
}
