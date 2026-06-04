<?php

declare(strict_types=1);

namespace App\Application\StravaImport\CalculateActivityMetrics;

use App\Application\StravaImport\CalculateActivityMetrics\Pipeline\CalculateActivityMetricsStep;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CalculateActivityMetricsCommandHandler implements CommandHandler
{
    /**
     * @param iterable<CalculateActivityMetricsStep> $steps
     */
    public function __construct(
        #[AutowireIterator('app.activity_calculate_metrics.pipeline_step')]
        private iterable $steps,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateActivityMetrics);

        $command->getOutput()->writeln('Calculating activity metrics. Please be patient, this can take a while...');

        foreach ($this->steps as $step) {
            $step->process($command->getOutput());
        }
    }
}
