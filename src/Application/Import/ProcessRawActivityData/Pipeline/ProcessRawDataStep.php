<?php

namespace App\Application\Import\ProcessRawActivityData\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_process_raw_data.pipeline_step')]
interface ProcessRawDataStep
{
    public function process(): void;
}
