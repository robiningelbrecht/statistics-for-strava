<?php

declare(strict_types=1);

namespace App\Controller\Admin\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\UpdateActivity\UpdateActivity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ManageActivityFormRequestHandler
{
    public function __construct(
        private Environment $twig,
        private ActivityRepository $activityRepository,
    ) {
    }

    #[Route(path: '/admin/activities/{activityId}/edit', name: 'admin_edit_activity', methods: ['GET'], priority: 10)]
    public function handle(string $activityId): Response
    {
        return new Response($this->twig->render('html/admin/page/activity/edit-activity.html.twig', [
            'dispatchCommand' => UpdateActivity::NAME,
            'activity' => $this->activityRepository->find(ActivityId::fromString($activityId)),
        ]));
    }
}
