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

        $first = null;
        $last = null;
        $middle = [];

        foreach ($steps as $step) {
            if ($step instanceof InitializeActivity) {
                $first = $step;
            } elseif ($step instanceof DownloadActivityImages) {
                $last = $step;
            } else {
                $middle[] = $step;
            }
        }

        $this->steps = array_filter([
            $first,
            ...$middle,
            $last,
        ]);
    }

    public function process(ActivityImportContext $context): ActivityImportContext
    {
        foreach ($this->steps as $step) {
            $context = $step->process($context);
        }

        return $context;
    }
}
