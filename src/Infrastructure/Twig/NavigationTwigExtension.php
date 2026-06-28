<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

final readonly class NavigationTwigExtension
{
    public function __construct(
        private RequestStack $requestStack,
    )
    {
    }

    /**
     * @param array<string, bool> $rules
     */
    #[AsTwigFunction('isActiveNavItem')]
    public function isActiveNavItem(array $rules): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return false;
        }

        $currentPath = $request->getPathInfo();
        foreach ($rules as $path => $exact) {
            if ($currentPath === $path) {
                return true;
            }
            if (!$exact && str_starts_with($currentPath, rtrim($path, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}
