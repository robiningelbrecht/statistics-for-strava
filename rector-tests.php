<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withPhpSets(php85: true)
    ->withComposerBased(phpunit: true)
    ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
        'allow_override_empty_method' => false,
    ]);
