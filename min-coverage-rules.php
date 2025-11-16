<?php

use RobinIngelbrecht\PHPUnitCoverageTools\MinCoverage\MinCoverageRule;

return [
    new MinCoverageRule(
        pattern: MinCoverageRule::TOTAL,
        minCoverage: 96,
        exitOnLowCoverage: false
    ),
    new MinCoverageRule(
        pattern: 'App\BuildApp\*',
        minCoverage: 97,
        exitOnLowCoverage: false
    ),
    new MinCoverageRule(
        pattern: 'App\Console\*',
        minCoverage: 100,
        exitOnLowCoverage: false
    ),
    new MinCoverageRule(
        pattern: 'App\Controller\*',
        minCoverage: 100,
        exitOnLowCoverage: false
    ),
    new MinCoverageRule(
        pattern: 'App\Infrastructure\*',
        minCoverage: 92,
        exitOnLowCoverage: false
    ),
    new MinCoverageRule(
        pattern: 'App\Domain\*',
        minCoverage: 96,
        exitOnLowCoverage: false
    ),
];
