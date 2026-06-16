<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Connection;

use App\Infrastructure\Config\PlatformEnvironment;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

final readonly class SqlitePragmasMiddleware implements Middleware
{
    public function __construct(
        private PlatformEnvironment $environment,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new SqlitePragmasDriver($driver, $this->environment);
    }
}
