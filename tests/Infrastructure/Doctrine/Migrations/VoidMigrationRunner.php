<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Migrations;

use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class VoidMigrationRunner implements MigrationRunner
{
    public function run(Application $application, OutputInterface $output): void
    {
    }

    public function isAtLatestVersion(Application $application): bool
    {
        return true;
    }
}
