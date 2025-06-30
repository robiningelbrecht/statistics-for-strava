<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\App\AppVersion;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Serialization\Json;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:environment', description: 'Outputs environment related debugging info')]
final class DebugEnvironmentConsoleCommand extends Command
{
    public function __construct(
        private readonly AppConfig $appConfig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Statistics for Strava');
        $io->text('Please copy all this output into the description of the bug ticket');
        $io->warning('Do not forget to redact sensitive information');

        $table = new Table($output);
        $table
            ->setHeaders(['ENV variable', 'Value'])
            ->setRows([
                ['APP_VERSION', AppVersion::getSemanticVersion()],
                ['STRAVA_CLIENT_ID', getenv('STRAVA_CLIENT_ID')],
                ['STRAVA_CLIENT_SECRET', getenv('STRAVA_CLIENT_SECRET')],
                ['STRAVA_REFRESH_TOKEN', getenv('STRAVA_REFRESH_TOKEN')],
                ['IMPORT_AND_BUILD_SCHEDULE', getenv('IMPORT_AND_BUILD_SCHEDULE')],
                ['TZ', getenv('TZ')],
                ['INSPECTOR_INGESTION_KEY', getenv('INSPECTOR_INGESTION_KEY')],
                new TableSeparator(),
                ['APP_CONFIG_GENERAL', Json::encodePretty($this->appConfig->get('general'))],
                new TableSeparator(),
                ['APP_CONFIG_IMPORT', Json::encodePretty($this->appConfig->get('import'))],
                new TableSeparator(),
                ['APP_CONFIG_APPEARANCE', Json::encodePretty($this->appConfig->get('appearance'))],
                new TableSeparator(),
                ['APP_CONFIG_ZWIFT', Json::encodePretty($this->appConfig->get('zwift'))],
            ]);
        $table->render();

        return Command::SUCCESS;
    }
}
