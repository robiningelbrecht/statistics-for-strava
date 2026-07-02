<?php

declare(strict_types=1);

namespace App\Console\Daemon;

use App\Application\RebuildStatus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait HandlesImportAndBuild
{
    public const string IMPORT_OPTION = 'import';
    public const string BUILD_OPTION = 'build';
    public const string IF_REQUIRED_OPTION = 'if-required';

    private function addImportAndBuildOptions(): void
    {
        $this->addOption(self::IMPORT_OPTION, null, InputOption::VALUE_NONE);
        $this->addOption(self::BUILD_OPTION, null, InputOption::VALUE_NONE);
    }

    private function addIfRequiredOption(): void
    {
        $this->addOption(self::IF_REQUIRED_OPTION, null, InputOption::VALUE_NONE);
    }

    /**
     * @return array{import: bool, build: bool}
     */
    private function resolvePhases(InputInterface $input): array
    {
        $runImport = (bool) $input->getOption(self::IMPORT_OPTION);
        $runBuild = (bool) $input->getOption(self::BUILD_OPTION);

        if (!$runImport && !$runBuild) {
            $runImport = $runBuild = true;
        }

        return [self::IMPORT_OPTION => $runImport, self::BUILD_OPTION => $runBuild];
    }

    private function appNeedsToBeBuilt(
        InputInterface $input,
        KeyValueStore $keyValueStore,
        RebuildStatus $rebuildStatus,
        string $today,
    ): bool {
        $phases = $this->resolvePhases($input);

        // Build when the build phase is requested and either --if-required was not passed
        // (always build), an import is running (fresh data), or a rebuild is actually required.
        return $phases[self::BUILD_OPTION]
            && (!$input->getOption(self::IF_REQUIRED_OPTION)
                || $phases[self::IMPORT_OPTION]
                || $this->aRebuildIsRequired(
                    keyValueStore: $keyValueStore,
                    rebuildStatus: $rebuildStatus,
                    today: $today
                ));
    }

    private function aRebuildIsRequired(
        KeyValueStore $keyValueStore,
        RebuildStatus $rebuildStatus,
        string $today,
    ): bool {
        try {
            $appLastBuiltOn = (string) $keyValueStore->find(Key::APP_LAST_BUILT_ON);
        } catch (EntityNotFound) {
            return true;
        }

        return $appLastBuiltOn !== $today || $rebuildStatus->isPending();
    }
}
