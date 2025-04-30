<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Strava\Strava;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        private Strava $strava,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/{wildcard?}', requirements: ['wildcard' => '.*'], methods: ['GET'])]
    public function handle(Request $request): Response
    {
        if ($this->buildStorage->fileExists('index.html')) {
            return new Response($this->buildStorage->read('index.html'), Response::HTTP_OK);
        }

        try {
            $this->strava->getAccessToken();
        } catch (ClientException|RequestException) {
            // Refresh token has not been set up properly, initialize authorization flow.
            return new RedirectResponse('/strava-oauth', Response::HTTP_FOUND);
        }

        return new Response($this->twig->render('html/setup.html.twig'), Response::HTTP_OK);
    }
}
