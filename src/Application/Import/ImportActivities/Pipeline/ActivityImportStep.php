<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_import.pipeline_step')]
interface ActivityImportStep
{
    public function process(ActivityImportContext $context): ActivityImportContext;
}
