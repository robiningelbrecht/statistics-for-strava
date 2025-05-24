<?php

namespace App\Tests\Infrastructure\DependencyInjection;

use App\Infrastructure\DependencyInjection\RegisterYamlConfig;
use App\Infrastructure\DependencyInjection\YamlConfigFiles;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterYamlConfigTest extends TestCase
{
    use MatchesSnapshots;

    private ContainerBuilder $containerBuilder;

    public function testProcess(): void
    {
        new RegisterYamlConfig(new YamlConfigFiles(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__),
            platformEnvironment: PlatformEnvironment::DEV
        ))->process($this->containerBuilder);

        $this->assertMatchesJsonSnapshot($this->containerBuilder->getParameterBag()->all());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->containerBuilder = new ContainerBuilder();
    }
}
