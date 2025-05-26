<?php

namespace App;

use App\Infrastructure\DependencyInjection\AppExpressionLanguageProvider;
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
}
