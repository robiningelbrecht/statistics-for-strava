<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ActivityImportPipeline
{
    /** @var ActivityImportStep[] */
    private array $steps;

    /**
     * @param iterable<ActivityImportStep> $steps
     */
    public function __construct(
        #[AutowireIterator('app.activity_import.pipeline_step')]
        iterable $steps,
    ) {
        $steps = iterator_to_array($steps);

        $this->steps = [
            ...array_filter($steps, fn (ActivityImportStep $step): bool => $step instanceof InitializeActivity),
            ...array_filter($steps, fn (ActivityImportStep $step): bool => !$step instanceof InitializeActivity),
        ];
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        foreach ($this->steps as $step) {
            $context = $step->process($context);
        }

        return $context;
    }
}
