<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__.'/rector-src.php');
    $rectorConfig->import(__DIR__.'/rector-tests.php');
};
