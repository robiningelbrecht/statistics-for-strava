<?php

declare(strict_types=1);

namespace App\Controller\Admin\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\DeleteActivity\DeleteActivity;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\UpdateActivity\UpdateActivity;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\RecordingDevice\RecordingDeviceRepository;
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
        private GearRepository $gearRepository,
        private RecordingDeviceRepository $recordingDeviceRepository,
    ) {
    }

    #[Route(path: '/admin/activities/{activityId}/edit', name: 'admin_edit_activity', methods: ['GET'], priority: 10)]
    public function handleEdit(string $activityId): Response
    {
        return new Response($this->twig->render('html/admin/page/activity/edit-activity.html.twig', [
            'dispatchCommand' => UpdateActivity::getCommandName(),
            'activity' => $this->activityRepository->find(ActivityId::fromString($activityId)),
            'sportTypes' => SportType::cases(),
            'gears' => $this->gearRepository->findAll()->sortByIsRetired(),
            'recordingDevices' => $this->recordingDeviceRepository->findAll(),
        ]));
    }

    #[Route(path: '/admin/activities/{activityId}/delete', name: 'admin_delete_activity', methods: ['GET'], priority: 10)]
    public function handleDelete(string $activityId): Response
    {
        return new Response($this->twig->render('html/admin/page/activity/delete-activity.html.twig', [
            'dispatchCommand' => DeleteActivity::getCommandName(),
            'activity' => $this->activityRepository->find(ActivityId::fromString($activityId)),
        ]));
    }
}
