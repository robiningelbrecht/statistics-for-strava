<?php

namespace App\Tests\Infrastructure\DependencyInjection;

use App\Infrastructure\DependencyInjection\CouldNotParseYamlConfig;
use App\Infrastructure\DependencyInjection\YamlConfigFiles;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use PHPUnit\Framework\TestCase;

class YamlConfigFilesTest extends TestCase
{
    public function testEnsureYamlFilesExist(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::configFileNotFound());

        new YamlConfigFiles(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__),
            platformEnvironment: PlatformEnvironment::TEST
        )->ensureYamlFilesExist();
    }
}
