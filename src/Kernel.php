<?php

namespace App;

use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\DependencyInjection\AppExpressionLanguageProvider;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Theme\Theme;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addExpressionLanguageProvider(new AppExpressionLanguageProvider());
    }

    #[\Override]
    protected function initializeContainer(): void
    {
        parent::initializeContainer();
        /** @var KeyValueStore $keyValueStore */
        $keyValueStore = $this->getContainer()->get(KeyValueStore::class);
        Theme::setKeyValueStore($keyValueStore);

        /** @var KernelProjectDir $kernelProjectDir */
        $kernelProjectDir = $this->getContainer()->get(KernelProjectDir::class);
        /** @var PlatformEnvironment $platformEnvironment */
        $platformEnvironment = $this->getContainer()->get(PlatformEnvironment::class);
        AppConfig::init(
            kernelProjectDir: $kernelProjectDir,
            platformEnvironment: $platformEnvironment
        );
    }
}
