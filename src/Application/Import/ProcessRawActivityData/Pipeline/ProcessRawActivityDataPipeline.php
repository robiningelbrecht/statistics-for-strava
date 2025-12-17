<?php

namespace App\Application\Import\ProcessRawActivityData\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ProcessRawActivityDataPipeline
{
    /**
     * @param iterable<ProcessRawDataStep> $steps
     */
    public function __construct(
        #[AutowireIterator('app.activity_process_raw_data.pipeline_step')]
        private iterable $steps,
    ) {
    }

    public function process(): void
    {
        foreach ($this->steps as $step) {
            $step->process();
        }
    }
}
