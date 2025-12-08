<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Symfony\Component\Console\Output\OutputInterface;

interface MigrationRunner
{
    public function run(OutputInterface $output): void;

    public function databaseIsInitialized(): bool;

    public function isAtLatestVersion(): bool;
}
