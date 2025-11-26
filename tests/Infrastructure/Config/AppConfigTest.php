<?php

namespace App\Tests\Infrastructure\Config;

use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Config\CouldNotParseYamlConfig;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppConfigTest extends TestCase
{
    use MatchesSnapshots;

    private ContainerBuilder $containerBuilder;

    #[DataProvider(methodName: 'provideConfig')]
    public function testGet(string $key, mixed $expectedValue, string $dir, PlatformEnvironment $platformEnvironment): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString($dir),
            platformEnvironment: $platformEnvironment
        );
        $this->assertEquals(
            $expectedValue,
            AppConfig::get($key)
        );
    }

    public function testGetWithDefaultValue(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/valid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );
        $default = [];
        $this->assertEquals(
            $default,
            AppConfig::get('non.existent.key', $default)
        );
    }

    public function testGetItShouldThrow(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/valid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );

        $this->expectExceptionObject(new \RuntimeException('Unknown configuration key "non.existent.key"'));
        AppConfig::get('non.existent.key');
    }

    public function testItThrowsExceptionWhenInvalidYmlInMainConfigFile(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::invalidYml('Malformed unquoted YAML string at line 1 (near "[}").'));

        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/invalid-config'),
            platformEnvironment: PlatformEnvironment::DEV
        );
    }

    public function testItThrowsExceptionWhenInvalidYmlInSubConfigFile(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::invalidYml('Malformed unquoted YAML string at line 1 (near "[}").'));

        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/invalid-config-sub'),
            platformEnvironment: PlatformEnvironment::DEV
        );
    }

    public function testItThrowsExceptionWhenFileIsMissing(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::configFileNotFound());

        AppConfig::init(
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
