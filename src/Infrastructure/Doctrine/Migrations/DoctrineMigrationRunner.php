<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class DoctrineMigrationRunner implements MigrationRunner
{
    public function run(Application $application, OutputInterface $output): void
    {
        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
        ]);
        $input->setInteractive(false);
        $exitCode = $application->doRun(
            input: $input,
            output: $output
        );

        if (0 !== $exitCode) {
            throw new CouldNotRunMigrations();
        }
    }

    public function isAtLatestVersion(Application $application): bool
    {
        $output = new MigrationConsoleOutput();
        $application->doRun(
            new ArrayInput([
                'command' => 'doctrine:migrations:status',
            ]),
            $output
        );

        return str_contains($output->getDisplay(), 'Already at latest version');
    }
}
