<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport\ImportActivityFiles\Pipeline;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_import_file.pipeline_step')]
interface ImportActivityFileStep
{
    public function process(ActivityImportContext $context): ActivityImportContext;
}
