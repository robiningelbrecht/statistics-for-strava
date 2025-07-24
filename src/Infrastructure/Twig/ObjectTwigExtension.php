<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigTest;

final readonly class ObjectTwigExtension
{
    /**
     * @param class-string $class
     */
    #[AsTwigTest('instanceof')]
    public function isInstanceOf(mixed $object, string $class): bool
    {
        if (!is_object($object)) {
            return false;
        }
        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->isInstance($object);
    }
}
