<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\App\ProfilePictureUrl;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class AIChatRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private ?ProfilePictureUrl $profilePictureUrl,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/ai/chat', methods: ['GET'], priority: 2)]
    public function handle(Request $request): Response
    {
        if (!$this->buildStorage->fileExists('index.html')) {
            return new RedirectResponse('/', Response::HTTP_FOUND);
        }

        return new Response($this->twig->render('html/ai-chat.html.twig', [
            'profilePictureUrl' => $this->profilePictureUrl,
        ]), Response::HTTP_OK);
    }
}
