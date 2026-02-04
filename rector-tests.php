<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

$rectorConfig = RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    );
