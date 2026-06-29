<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gear\RecordingDevice;

use App\Domain\Gear\RecordingDevice\RecordingDeviceId;
use App\Domain\Gear\RecordingDevice\RecordingDeviceRepository;
use App\Domain\Gear\RecordingDevice\UpdateRecordingDevice\UpdateRecordingDevice;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ManageRecordingDeviceFormRequestHandler
{
    public function __construct(
        private Environment $twig,
        private RecordingDeviceRepository $recordingDeviceRepository,
    ) {
    }

    #[Route(path: '/admin/gear/recording-devices/{id}/edit', name: 'admin_edit_recording_device', methods: ['GET'], priority: 10)]
    public function handleEdit(string $id): Response
    {
        return new Response($this->twig->render('html/admin/page/gear/recording-device/edit-recording-device.html.twig', [
            'dispatchCommand' => UpdateRecordingDevice::getCommandName(),
            'recordingDevice' => $this->recordingDeviceRepository->find(RecordingDeviceId::fromString($id)),
        ]));
    }
}
