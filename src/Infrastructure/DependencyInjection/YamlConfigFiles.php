<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;

final readonly class YamlConfigFiles
{
    public function __construct(
        private KernelProjectDir $kernelProjectDir,
        private PlatformEnvironment $platformEnvironment,
    ) {
    }

    public function ensureYamlFilesExist(): void
    {
        foreach ($this->getFiles() as $yamlFile) {
            if ($yamlFile->isRequired() && !file_exists($yamlFile->getFilePath())) {
                throw CouldNotParseYamlConfig::configFileNotFound();
            }
        }
    }

    /**
     * @return YamlConfigFile[]
     */
    public function getFiles(): array
    {
        $basePath = $this->kernelProjectDir->getConfigBasePath();

        return [
            new YamlConfigFile(
                filePath: $basePath.($this->platformEnvironment->isTest() ? 'config_test.yaml' : 'config.yaml'),
                isRequired: true,
                needsNestedProcessing: true,
                prefix: 'app.config',
            ),
            new YamlConfigFile(
                filePath: $basePath.($this->platformEnvironment->isTest() ? 'gear-maintenance_test.yaml' : 'gear-maintenance.yaml'),
                isRequired: true,
                needsNestedProcessing: false,
                prefix: 'app.gear_maintenance',
            ),
        ];
    }
}
