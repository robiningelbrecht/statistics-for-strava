<?php

namespace App\Tests\Infrastructure\DependencyInjection;

use App\Infrastructure\DependencyInjection\CouldNotParseYamlConfig;
use App\Infrastructure\DependencyInjection\RegisterYamlConfig;
use App\Infrastructure\DependencyInjection\YamlConfigFile;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterYamlConfigTest extends TestCase
{
    use MatchesSnapshots;

    private ContainerBuilder $containerBuilder;

    public function testProcess(): void
    {
        new RegisterYamlConfig([
            new YamlConfigFile(
                filePath: __DIR__.'/needs-nesting.yml',
                isRequired: false,
                needsNestedProcessing: true,
                prefix: 'prefix'
            ),
            new YamlConfigFile(
                filePath: __DIR__.'/does-not-need-besting.yml',
                isRequired: true,
                needsNestedProcessing: false,
                prefix: 'prefix'
            ),
        ])->process($this->containerBuilder);

        $this->assertMatchesJsonSnapshot($this->containerBuilder->getParameterBag()->all());
    }

    public function testProcessWhenConfigFileNotFound(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::configFileNotFound());

        new RegisterYamlConfig([
            new YamlConfigFile(
                filePath: 'non-existing-but-not-required.yml',
                isRequired: false,
                needsNestedProcessing: true,
                prefix: 'prefix'
            ),
            new YamlConfigFile(
                filePath: 'non-existing.yml',
                isRequired: true,
                needsNestedProcessing: true,
                prefix: 'prefix'
            ),
        ])->process($this->containerBuilder);
    }

    public function testProcessWhenInvalidYaml(): void
    {
        $this->expectExceptionObject(CouldNotParseYamlConfig::invalidYml('Malformed unquoted YAML string at line 1 (near "[}").'));
        new RegisterYamlConfig([
            new YamlConfigFile(
                filePath: __DIR__.'/invalid.yml',
                isRequired: false,
                needsNestedProcessing: true,
                prefix: 'prefix'
            ),
        ])->process($this->containerBuilder);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->containerBuilder = new ContainerBuilder();
    }
}
