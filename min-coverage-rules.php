<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: MinCoverageRule::TOTAL,
        minCoverage: 96,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\BuildApp\*',
        minCoverage: 95,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Console\*',
        minCoverage: 100,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Controller\*',
        minCoverage: 100,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Infrastructure\*',
        minCoverage: 91,
        exitOnLowCoverage: true
    ),
    new MinCoverageRule(
        pattern: 'App\Domain\*',
        minCoverage: 97,
        exitOnLowCoverage: true
    ),
];
