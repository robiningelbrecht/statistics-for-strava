<?php

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_calculate_metrics.pipeline_step')]
interface CalculateActivityMetricsStep
{
    public function process(OutputInterface $output): void;
}
