<?php

namespace App\Application\Import\ProcessRawActivityData;

use App\Application\Import\ProcessRawActivityData\Pipeline\ProcessRawActivityDataPipeline;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class ProcessRawActivityDataCommandHandler implements CommandHandler
{
    public function __construct(
        private ProcessRawActivityDataPipeline $pipeline,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ProcessRawActivityData);

        $this->pipeline->process();
    }
}
