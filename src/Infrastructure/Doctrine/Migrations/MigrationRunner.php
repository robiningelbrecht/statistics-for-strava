<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

interface MigrationRunner
{
    public function run(Application $application, OutputInterface $output): void;

    public function isAtLatestVersion(Application $application): bool;
}
