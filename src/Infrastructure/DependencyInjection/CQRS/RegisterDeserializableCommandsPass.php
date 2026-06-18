<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\CQRS;

use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommandRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class RegisterDeserializableCommandsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $srcDir = $container->getParameter('kernel.project_dir');
        \assert(is_string($srcDir));
        $srcDir .= '/src';
        if (!is_dir($srcDir)) {
            return;
        }

        $commandsById = [];
        foreach (new Finder()->files()->in($srcDir)->name('*.php') as $file) {
            if (!str_contains($file->getContents(), 'AsDeserializableCommand')) {
                continue;
            }

            $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            if (!class_exists($class)) {
                continue;
            }
            if (!is_a($class, DeserializableCommand::class, true)) {
                continue;
            }

            $attributes = new \ReflectionClass($class)->getAttributes(AsDeserializableCommand::class);
            if ([] === $attributes) {
                continue;
            }

            $id = $attributes[0]->newInstance()->id;
            if (isset($commandsById[$id])) {
                throw new \LogicException(sprintf('Duplicate deserializable command id "%s" on "%s" and "%s".', $id, $commandsById[$id], $class));
            }
            $commandsById[$id] = $class;
        }

        $container->getDefinition(DeserializableCommandRegistry::class)->setArgument(0, $commandsById);
    }
}
