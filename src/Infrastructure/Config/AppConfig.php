<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class AppConfig
{
    private static ?KernelProjectDir $kernelProjectDir = null;
    private static ?PlatformEnvironment $platformEnvironment = null;
    /** @var YamlConfigFile[] */
    private static array $ymlFilesToProcess = [];

    /** @var array<string|int, string|int|float|array<string|int,mixed>|null> */
    private static array $config = [];

    public static function init(
        KernelProjectDir $kernelProjectDir,
        PlatformEnvironment $platformEnvironment,
    ): void {
        self::$kernelProjectDir = $kernelProjectDir;
        self::$platformEnvironment = $platformEnvironment;
        self::buildConfig();
    }

    private static function buildConfig(): void
    {
        if (null === self::$kernelProjectDir) {
            throw new \RuntimeException('$kernelProjectDir not set. Please call AppConfig::setServices() before using this method.');
        }
        if (null === self::$platformEnvironment) {
            throw new \RuntimeException('$platformEnvironment not set. Please call AppConfig::setServices() before using this method.');
        }
        self::$config = [];
        self::$ymlFilesToProcess = [];
        $basePath = self::$kernelProjectDir.'/config/app/';
        $isTest = self::$platformEnvironment->isTest();

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

            self::$ymlFilesToProcess[] = $yamlFile;

            try {
                self::processYamlConfig(
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
    private static function processYamlConfig(
        array $ymlConfig,
        bool $needsNestedProcessing,
        ?string $prefix): void
    {
        if (!$needsNestedProcessing) {
            // @phpstan-ignore-next-line
            self::$config[$prefix] = $ymlConfig;

            return;
        }

        foreach ($ymlConfig as $key => $value) {
            if (is_string($key) && str_contains($key, '_')) {
                // This key is in snake_case, convert it to camelCase to make sure this stays backwards compatible
                $key = lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }

            $fullKey = (string) (null === $prefix ? $key : "$prefix.$key");
            if (array_key_exists($fullKey, self::$config)) {
                throw new CouldNotParseYamlConfig(sprintf('Duplicate config key: %s', $fullKey));
            }
            self::$config[$fullKey] = $value;

            if (is_array($value)) {
                self::processYamlConfig(
                    ymlConfig: $value,
                    needsNestedProcessing: true,
                    prefix: $fullKey
                );
            }
        }
    }

    /**
     * @return YamlConfigFile[]
     */
    public static function getYamlFilesToProcess(): array
    {
        return self::$ymlFilesToProcess;
    }

    /**
     * @return string|int|float|array<string|int,mixed>|null
     */
    public static function get(string $key, mixed $default = null): string|int|float|array|bool|null
    {
        if (!array_key_exists($key, self::$config)) {
            if (null !== $default) {
                return $default;
            }

            throw new \RuntimeException(sprintf('Unknown configuration key "%s"', $key));
        }

        return self::$config[$key];
    }

    public static function isAIIntegrationEnabled(): bool
    {
        return !empty(self::get('integrations.ai.enabled', false));
    }

    public static function isAIIntegrationWithUIEnabled(): bool
    {
        return self::isAIIntegrationEnabled() && !empty(self::get('integrations.ai.enableUI', false));
    }

    /**
     * @return array<string|int, mixed>
     */
    public static function getRoot(): array
    {
        $root = [];

        foreach (self::$config as $name => $value) {
            if (str_contains((string) $name, '.')) {
                continue;
            }

            $root[$name] = $value;
        }

        return $root;
    }
}
