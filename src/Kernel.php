<?php

namespace App;

use App\Infrastructure\DependencyInjection\RegisterYamlConfig;
use App\Infrastructure\DependencyInjection\YamlConfigFiles;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function initializeContainer(): void
    {
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($this->getCacheDir())) {
            // Config has changed, remove old cache entries to manage disk space.
            $fileSystem->remove(dirname($this->getCacheDir()));
        }
        parent::initializeContainer();
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterYamlConfig($this->getYamlFilesToProcess()));
    }

    public function getCacheDir(): string
    {
        $fileSystem = new Filesystem();
        $configContents = '';

        foreach ($this->getYamlFilesToProcess()->getFiles() as $yamlFile) {
            try {
                $configContents .= $fileSystem->readFile($yamlFile->getFilePath());
            } catch (IOException) {
            }
        }

        // Cache dir is now dependent on config. Everytime config changes, cache will be rebuilt.
        return parent::getCacheDir().'/'.sha1($configContents);
    }

    private function getYamlFilesToProcess(): YamlConfigFiles
    {
        return new YamlConfigFiles(
            kernelProjectDir: KernelProjectDir::fromString($this->getProjectDir()),
            platformEnvironment: PlatformEnvironment::from($this->getEnvironment())
        );
    }
}
