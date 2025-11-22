<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var');

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder);
