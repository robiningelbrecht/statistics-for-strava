<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Migrations;

use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use Doctrine\DBAL\Driver\SQLite3\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

final class VoidMigrationRunner implements MigrationRunner
{
    private bool $throwOnNextRun = false;

    public function throwOnNextRun(): void
    {
        $this->throwOnNextRun = true;
    }

    public function run(OutputInterface $output): void
    {
        if ($this->throwOnNextRun) {
            throw new ConnectionException(new Exception(new \RuntimeException()), null);
        }
        $this->throwOnNextRun = false;
    }

    public function isAtLatestVersion(): bool
    {
        if ($this->throwOnNextRun) {
            throw new ConnectionException(new Exception('test'), null);
        }
        $this->throwOnNextRun = false;

        return true;
    }
}
