<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Mutex;

use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowireWithMutexPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $createdMutexes = [];
        foreach ($container->findTaggedServiceIds('app.mutex') as $id => $tags) {
            $tag = array_first($tags);

            if (!$mutexDefinitionId = $tag['mutex'] ?? null) {
                continue;
            }

            if (!\in_array($mutexDefinitionId, $createdMutexes, true)) {
                $definitionArguments = [];
                if (!$constructorParams = new \ReflectionClass(Mutex::class)->getConstructor()?->getParameters() ?? []) {
                    continue;
                }

                [$prefix, $lockName] = explode('.', (string) $mutexDefinitionId);

                foreach ($constructorParams as $param) {
                    /** @var \ReflectionNamedType $type */
                    $type = $param->getType();
                    $definitionArguments[] = match ($param->getName()) {
                        'lockName' => LockName::from($lockName),
                        default => new Reference($type->getName()),
                    };
                }

                $container->setDefinition(
                    id: $mutexDefinitionId,
                    definition: new Definition(
                        class: Mutex::class,
                        arguments: $definitionArguments
                    )
                );
                $createdMutexes[] = $mutexDefinitionId;
            }

            $definition = $container->getDefinition($id);
            $binding = new BoundArgument(new Reference($mutexDefinitionId));

            $bindings = $definition->getBindings();
            $bindings[Mutex::class] = $binding;
            $definition->setBindings($bindings);
        }
    }
}
