<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait ConfiguresImportAndBuildPhases
{
    public const string IMPORT_OPTION = 'import';
    public const string BUILD_OPTION = 'build';

    private function addImportAndBuildOptions(): void
    {
        $this->addOption(self::IMPORT_OPTION, null, InputOption::VALUE_NONE);
        $this->addOption(self::BUILD_OPTION, null, InputOption::VALUE_NONE);
    }

    /**
     * @return array{import: bool, build: bool} Which phases to run.
     *                                          Nothing specified → run everything.
     */
    private function resolvePhases(InputInterface $input): array
    {
        $runImport = (bool) $input->getOption(self::IMPORT_OPTION);
        $runBuild = (bool) $input->getOption(self::BUILD_OPTION);

        if (!$runImport && !$runBuild) {
            $runImport = $runBuild = true;
        }

        return ['import' => $runImport, 'build' => $runBuild];
    }
}
