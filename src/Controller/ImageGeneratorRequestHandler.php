<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\App\BuildIndexHtml\IndexHtml;
use App\Domain\App\Router;
use App\Infrastructure\Time\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class ImageGeneratorRequestHandler
{
    public function __construct(
        private IndexHtml $indexHtml,
        private FilesystemOperator $buildStorage,
        private Environment $twig,
        private Clock $clock,
    ) {
    }

    #[Route(path: '/image-generator', methods: ['GET'], priority: 2)]
    public function handle(Request $request): Response
    {
        if (!$this->buildStorage->fileExists('index.html')) {
            return new RedirectResponse('/', Response::HTTP_FOUND);
        }

        return new Response($this->twig->render(
            'html/image-generator.html.twig',
            [
                'router' => Router::SYMFONY,
                ...$this->indexHtml->getContext($this->clock->getCurrentDateTimeImmutable()),
            ]
        ), Response::HTTP_OK);
    }
}
