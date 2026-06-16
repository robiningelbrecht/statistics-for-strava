<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class DashboardRequestHandler
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    #[Route(path: '/admin', name: 'admin_dashboard', methods: ['GET'], priority: 10)]
    public function index(): Response
    {
        return new Response($this->twig->render('html/admin/dashboard.html.twig'));
    }
}
