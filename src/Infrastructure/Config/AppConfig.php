<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class AppConfig
{
    private static ?KernelProjectDir $kernelProjectDir = null;
    private static ?PlatformEnvironment $platformEnvironment = null;
    /** @var non-empty-array<string> */
    private static array $ymlConfigFiles;

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
            throw new \RuntimeException('$kernelProjectDir not set. Please call AppConfig::setServices() before using this method.'); // @codeCoverageIgnore
        }
        if (null === self::$platformEnvironment) {
            throw new \RuntimeException('$platformEnvironment not set. Please call AppConfig::setServices() before using this method.'); // @codeCoverageIgnore
        }
        self::$config = [];
        $isTest = self::$platformEnvironment->isTest();
        $basePath = !$isTest ? self::$kernelProjectDir.'/config/app' : self::$kernelProjectDir.'/config/app/test';

        $mainConfigFile = $basePath.'/config.yaml';
        if (!file_exists($mainConfigFile)) {
            throw CouldNotParseYamlConfig::configFileNotFound();
        }

        try {
            $parsedYaml = Yaml::parseFile($mainConfigFile);
            self::$ymlConfigFiles = [$mainConfigFile];
        } catch (ParseException $e) {
            throw CouldNotParseYamlConfig::invalidYml($e->getMessage());
        }

        try {
            $finder = Finder::create()
                ->in($basePath)
                ->depth('== 0')
                ->files()
                ->name('config-*.yaml');

            foreach ($finder as $file) {
                try {
                    $parsedYaml = array_replace_recursive($parsedYaml, Yaml::parseFile($file->getRealPath()));
                    self::$ymlConfigFiles[] = $file->getRealPath();
                } catch (ParseException $e) {
                    throw CouldNotParseYamlConfig::invalidYml($e->getMessage());
                }
            }
        } catch (DirectoryNotFoundException) {
        }

        self::processYamlConfig(
            ymlConfig: $parsedYaml,
            prefix: null
        );

        $pathMaintenanceConfigFile = $basePath.'/gear-maintenance.yaml';
        if (file_exists($pathMaintenanceConfigFile)) {
            self::$ymlConfigFiles[] = $pathMaintenanceConfigFile;
            self::$config['gearMaintenance'] = Yaml::parseFile($pathMaintenanceConfigFile);
        }
    }

    /**
     * @param array<string|int, mixed> $ymlConfig
     */
    private static function processYamlConfig(array $ymlConfig, ?string $prefix): void
    {
        foreach ($ymlConfig as $key => $value) {
            if (is_string($key) && str_contains($key, '_')) {
                // This key is in snake_case, convert it to camelCase to make sure this stays backwards compatible
                $key = lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }

            $fullKey = (string) (null === $prefix ? $key : "$prefix.$key");
            if (array_key_exists($fullKey, self::$config)) {
                throw new CouldNotParseYamlConfig(sprintf('Duplicate config key: %s', $fullKey)); // @codeCoverageIgnore
            }
            self::$config[$fullKey] = $value;

            if (is_array($value)) {
                self::processYamlConfig(
                    ymlConfig: $value,
                    prefix: $fullKey
                );
            }
        }
    }

    /**
     * @return non-empty-array<string>
     */
    public static function getYamlFilesToProcess(): array
    {
        return self::$ymlConfigFiles;
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
