<?php

declare(strict_types=1);

namespace App\Controller\Admin\File;

use App\Domain\Import\SupportedFileExtension;
use App\Infrastructure\Serialization\Json;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class FileUploadRequestHandler
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    #[Route(path: '/admin', name: 'admin', methods: ['GET'], priority: 10)]
    #[Route(path: '/admin/upload', name: 'admin_file_upload', methods: ['GET'], priority: 10)]
    public function index(): Response
    {
        return new Response($this->twig->render('html/admin/page/file/file-upload.html.twig', [
            'supportedFileExtensions' => Json::encode(SupportedFileExtension::cases()),
        ]));
    }
}
