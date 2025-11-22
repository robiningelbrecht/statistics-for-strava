<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class AppConfig
{
    /** @var array<string|int, string|int|float|array<string|int,mixed>|null> */
    private array $config = [];

    public function __construct(
        private readonly KernelProjectDir $kernelProjectDir,
        private readonly PlatformEnvironment $platformEnvironment,
    ) {
        $this->buildConfig();
    }

    private function buildConfig(): void
    {
        $basePath = $this->kernelProjectDir.'/config/app/';
        $isTest = $this->platformEnvironment->isTest();

        $ymlFilesToProcess = [
            new YamlConfigFile(
                filePath: $basePath.($isTest ? 'config_test.yaml' : 'config.yaml'),
                isRequired: true,
                needsNestedProcessing: true,
                prefix: null,
            ),
            new YamlConfigFile(
                filePath: $basePath.($isTest ? 'gear-maintenance_test.yaml' : 'gear-maintenance.yaml'),
                isRequired: false,
                needsNestedProcessing: false,
                prefix: 'gearMaintenance',
            ),
        ];

        /** @var YamlConfigFile $yamlFile */
        foreach ($ymlFilesToProcess as $yamlFile) {
            if ($yamlFile->isRequired() && !file_exists($yamlFile->getFilePath())) {
                throw CouldNotParseYamlConfig::configFileNotFound();
            }

            if (!file_exists($yamlFile->getFilePath())) {
                continue;
            }

            try {
                $this->processYamlConfig(
                    ymlConfig: Yaml::parseFile($yamlFile->getFilePath()),
                    needsNestedProcessing: $yamlFile->needsNestedProcessing(),
                    prefix: $yamlFile->getPrefix()
                );
            } catch (ParseException $e) {
                throw CouldNotParseYamlConfig::invalidYml($e->getMessage());
            }
        }
    }

    /**
     * @param array<string|int, mixed> $ymlConfig
     */
    private function processYamlConfig(
        array $ymlConfig,
        bool $needsNestedProcessing,
        ?string $prefix): void
    {
        if (!$needsNestedProcessing) {
            // @phpstan-ignore-next-line
            $this->config[$prefix] = $ymlConfig;

            return;
        }

        foreach ($ymlConfig as $key => $value) {
            if (is_string($key) && str_contains($key, '_')) {
                // This key is in snake_case, convert it to camelCase to make sure this stays backwards compatible
                $key = lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }

            $fullKey = (string) (null === $prefix ? $key : "$prefix.$key");
            if (array_key_exists($fullKey, $this->config)) {
                throw new CouldNotParseYamlConfig(sprintf('Duplicate config key: %s', $fullKey));
            }
            $this->config[$fullKey] = $value;

            if (is_array($value)) {
                $this->processYamlConfig(
                    ymlConfig: $value,
                    needsNestedProcessing: true,
                    prefix: $fullKey
                );
            }
        }
    }

    /**
     * @return string|int|float|array<string|int,mixed>|null
     */
    public function get(string $key, mixed $default = null): string|int|float|array|bool|null
    {
        if (!array_key_exists($key, $this->config)) {
            if (null !== $default) {
                return $default;
            }

            throw new \RuntimeException(sprintf('Unknown configuration key "%s"', $key));
        }

        return $this->config[$key];
    }

    public function AIIntegrationIsEnabled(): bool
    {
        return !empty($this->get('integrations.ai.enabled', false));
    }

    public function AIIntegrationWithUIIsEnabled(): bool
    {
        return $this->AIIntegrationIsEnabled() && !empty($this->get('integrations.ai.enableUI', false));
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getRoot(): array
    {
        $root = [];

        foreach ($this->config as $name => $value) {
            if (str_contains((string) $name, '.')) {
                continue;
            }

            $root[$name] = $value;
        }

        return $root;
    }
}
