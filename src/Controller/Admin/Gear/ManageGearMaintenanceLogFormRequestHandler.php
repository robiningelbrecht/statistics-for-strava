<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gear;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\Log\AddGearMaintenanceLog\AddGearMaintenanceLog;
use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogId;
use App\Domain\Gear\Maintenance\Log\GearMaintenanceLogRepository;
use App\Domain\Gear\Maintenance\Log\UpdateGearMaintenanceLog\UpdateGearMaintenanceLog;
use App\Domain\Gear\Maintenance\Task\MaintenanceTask;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Exception\EntityNotFound;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ManageGearMaintenanceLogFormRequestHandler
{
    public function __construct(
        private Environment $twig,
        private AppConfig $config,
        private GearRepository $gearRepository,
        private GearMaintenanceLogRepository $gearMaintenanceLogRepository,
    ) {
    }

    #[Route(path: '/admin/gear/maintenance-logs/register', name: 'admin_register_gear_maintenance_log', methods: ['GET'], priority: 10)]
    public function handleAdd(): Response
    {
        return new Response($this->twig->render('html/admin/page/gear/edit-gear-maintenance-log.html.twig', [
            'dispatchCommand' => AddGearMaintenanceLog::NAME,
            'components' => $this->config->loadGearMaintenance()->buildComponentOptions($this->gearRepository->findAll()),
        ]));
    }

    #[Route(path: '/admin/gear/maintenance-logs/{gearMaintenanceLogId}/edit', name: 'admin_edit_gear_maintenance_log', methods: ['GET'], priority: 10)]
    public function handleEdit(string $gearMaintenanceLogId): Response
    {
        $gearMaintenanceLog = $this->gearMaintenanceLogRepository->find(GearMaintenanceLogId::fromString($gearMaintenanceLogId));

        $gearMaintenanceConfig = $this->config->loadGearMaintenance();
        $component = $gearMaintenanceConfig->findGearComponentForMaintenanceTask($gearMaintenanceLog->getMaintenanceTaskId());
        $task = $gearMaintenanceConfig->findMaintenanceTask($gearMaintenanceLog->getMaintenanceTaskId());
        $gear = $this->gearRepository->findAll()->getByGearId($gearMaintenanceLog->getGearId());

        if (!$gear instanceof Gear || !$component instanceof GearComponent || !$task instanceof MaintenanceTask) {
            throw new EntityNotFound(sprintf('Gear maintenance log "%s" is no longer editable', $gearMaintenanceLogId));
        }

        return new Response($this->twig->render('html/admin/page/gear/edit-gear-maintenance-log.html.twig', [
            'dispatchCommand' => UpdateGearMaintenanceLog::NAME,
            'gearMaintenanceLog' => $gearMaintenanceLog,
            'componentLabel' => (string) $component->getLabel(),
            'taskLabel' => (string) $task->getLabel(),
            'gearName' => $gear->getName(),
        ]));
    }
}
