<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\RebuildStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class RebuildStatusRequestHandler
{
    public function __construct(
        private RebuildStatus $rebuildStatus,
    ) {
    }

    #[Route(path: '/admin/rebuildStatus', name: 'admin_rebuild_status', methods: ['GET'], priority: 10)]
    public function handle(): JsonResponse
    {
        return new JsonResponse(['pending' => $this->rebuildStatus->isPending()]);
    }
}
