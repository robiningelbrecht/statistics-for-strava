<?php

namespace App\Application\Import\FileImport\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ActivityFileImportPipeline
{
    /** @var ImportActivityFileStep[] */
    private array $steps;

    /**
     * @param iterable<ImportActivityFileStep> $steps
     */
    public function __construct(
        #[AutowireIterator('app.activity_import_file.pipeline_step')]
        iterable $steps,
    ) {
        $steps = iterator_to_array($steps);

        $first = null;
        $other = [];

        foreach ($steps as $step) {
            if ($step instanceof ParseActivityFile) {
                $first = $step;
            } else {
                $other[] = $step;
            }
        }

        $this->steps = array_filter([
            $first,
            ...$other,
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
