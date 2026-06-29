<?php

namespace App\Tests\Infrastructure\Config;

use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Config\CouldNotParseYamlConfig;
use App\Infrastructure\Config\PlatformEnvironment;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AppConfigTest extends ContainerTestCase
{
    #[DataProvider(methodName: 'provideConfig')]
    public function testGet(string $key, mixed $expectedValue, string $dir, PlatformEnvironment $platformEnvironment): void
    {
        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString($dir),
            platformEnvironment: $platformEnvironment
        );

        $config = new AppConfig();

        $this->assertEquals(
            $expectedValue,
            $config->get($key)
        );
    }

    public function testGetWithDefaultValue(): void
    {
        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/valid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );

        $config = new AppConfig();

        $default = [];
        $this->assertEquals(
            $default,
            $config->get('non.existent.key', $default)
        );
    }

    public function testGetItShouldThrowWhenKeyIsMissing(): void
    {
        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/valid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );

        $config = new AppConfig();

        $this->expectExceptionObject(new \RuntimeException('Unknown configuration key "non.existent.key"'));
        $config->get('non.existent.key');
    }

    public function testItThrowsExceptionWhenInvalidYmlInMainConfigFile(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::invalidYml('Malformed unquoted YAML string at line 1 (near "[}").'));

        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/invalid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );

        new AppConfig();
    }

    public function testItThrowsExceptionWhenInvalidYmlInSubConfigFile(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::invalidYml('Malformed unquoted YAML string at line 1 (near "[}").'));

        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/invalid-config-sub'),
            platformEnvironment: PlatformEnvironment::DEV
        );

        new AppConfig();
    }

    public function testItThrowsExceptionWhenFileIsMissing(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::configFileNotFound());

        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString('lol'),
            platformEnvironment: PlatformEnvironment::DEV
        );
    }

    public static function provideConfig(): array
    {
        return [
            ['general.athlete.birthday', '1989-08-14', __DIR__.'/valid-config', PlatformEnvironment::DEV],
            ['zwift', ['level' => 80, 'racing_score' => 495], __DIR__.'/valid-config', PlatformEnvironment::DEV],
            ['zwift.racingScore', 495, __DIR__.'/valid-config', PlatformEnvironment::DEV],
        ];
    }
}
