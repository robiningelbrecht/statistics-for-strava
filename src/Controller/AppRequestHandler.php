<?php

declare(strict_types=1);

namespace App\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class AppRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/{wildcard?}', methods: ['GET'])]
    public function handle(Request $request): Response
    {
        if ($this->buildStorage->fileExists('index.html')) {
            return new Response($this->buildStorage->read('index.html'), Response::HTTP_OK);
        }

        return new Response($this->twig->render('html/setup.html.twig'), Response::HTTP_OK);
    }
}
