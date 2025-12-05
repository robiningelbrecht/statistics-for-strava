<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\AppVersion;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Time\Clock\Clock;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;

trait ProvideConsoleIntro
{
    public function outputConsoleIntro(SymfonyStyle $output): void
    {
        $output->block(
            messages: [
                sprintf('Statistics for Strava %s', AppVersion::getSemanticVersion()),
            ],
            style: 'fg=black;bg=green',
            padding: true
        );
        $this->outputRuntimeAndConfig($output);
    }

    public function outputDaemonConsoleIntro(SymfonyStyle $output, Clock $clock): void
    {
        $output->block(
            messages: [
                sprintf('Statistics for Strava %s | DAEMON', AppVersion::getSemanticVersion()),
                sprintf('Started on %s', $clock->getCurrentDateTimeImmutable()->format('d-m-Y H:i:s')),
            ],
            style: 'fg=black;bg=green',
            padding: true
        );
        $this->outputRuntimeAndConfig($output);
    }

    private function outputRuntimeAndConfig(SymfonyStyle $output): void
    {
        $configFilesToProcess = array_map(
            fn (string $configFile): string => sprintf('  * %s', $configFile),
            AppConfig::getYamlFilesToProcess(),
        );

        $maxStringLength = max(array_map(Helper::width(...), $configFilesToProcess)) + 5;

        $output->writeln(str_repeat('-', $maxStringLength));
        $output->newLine();
        $output->text(sprintf('Runtime: PHP %s (%s) on %s', PHP_VERSION, PHP_SAPI, PHP_OS));
        $output->newLine();
        $output->text('Configuration files:');
        $output->writeln($configFilesToProcess);
        $output->newLine();
        $output->writeln(str_repeat('-', $maxStringLength));
        $output->newLine();
    }
}
