<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gear\Maintenance;

use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Gear\Maintenance\UpdateGearMaintenanceConfig\UpdateGearMaintenanceConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ManageGearMaintenanceConfigRequestHandler
{
    public function __construct(
        private Environment $twig,
        private GearMaintenanceRepository $gearMaintenanceRepository,
        private GearRepository $gearRepository,
    ) {
    }

    #[Route(path: '/admin/gear/maintenance-config', name: 'admin_manage_gear_maintenance_config', methods: ['GET'], priority: 10)]
    public function handle(): Response
    {
        return new Response($this->twig->render('html/admin/page/gear/maintenance/config.html.twig', [
            'dispatchCommand' => UpdateGearMaintenanceConfig::getCommandName(),
            'gearMaintenanceConfig' => $this->gearMaintenanceRepository->find(),
            'gears' => $this->gearRepository->findAll(),
        ]));
    }
}
