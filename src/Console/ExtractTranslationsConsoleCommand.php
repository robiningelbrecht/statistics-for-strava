<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\Localisation\Locale;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'app:translations:extract', description: 'Extract translations for all locales')]
class ExtractTranslationsConsoleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (Locale::cases() as $locale) {
            $process = new Process(
                command: [
                    '/var/www/bin/console',
                    'translation:extract',
                    '--force',
                    '--prefix=',
                    '--domain=messages',
                    '--format=yaml',
                    '--sort=ASC',
                    $locale->value,
                ],
                timeout: null
            );

            $process->run();
            $output->writeln(sprintf('<info>Extracted translations for "%s"</info>', $locale->value));
        }

        return Command::SUCCESS;
    }
}
