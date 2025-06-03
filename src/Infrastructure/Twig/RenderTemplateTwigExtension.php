<?php

namespace App\Infrastructure\Twig;

use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

final readonly class RenderTemplateTwigExtension
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @param array<mixed> $context
     */
    #[AsTwigFunction('renderComponent')]
    public function renderComponent(string $template, array $context = []): string
    {
        return $this->render(sprintf('html/component/%s.html.twig', $template), $context);
    }

    /**
     * @param array<mixed> $context
     */
    #[AsTwigFunction('renderSvg')]
    public function renderSvg(string $template, array $context = []): string
    {
        if (empty($context['customPath'])) {
            return $this->render(sprintf('svg/svg-%s.html.twig', $template), $context);
        }

        return $this->render(sprintf('%s/svg-%s.html.twig', $context['customPath'], $template), $context);
    }

    /**
     * @param array<mixed> $context
     */
    private function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}
